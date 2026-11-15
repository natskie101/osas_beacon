<?php

declare(strict_types=1);

/**
 * Login flow + throttle probe through the HTTP kernel (no web server needed).
 *
 * Covers, against a SEEDED SCRATCH database:
 *   1. Successful sign-in for all three seeded roles, each landing on its
 *      Role::homePath() and rendering an authenticated dashboard.
 *   2. Per-account lockout: 5 wrong passwords -> "Incorrect email or
 *      password", the 6th -> "Too many failed attempts" for that account
 *      only, while a different account from the same IP stays unaffected.
 *   3. Route throttle: the 21st POST /login from one IP -> branded 429 page,
 *      while GET /login and other IPs are still served.
 *
 * Two in-kernel quirks are handled explicitly — a real PHP-FPM deployment
 * gets both right because every request is a fresh process:
 *
 *   - Per-phase IP isolation: trustProxies('*') trusts only the calling IP
 *     (REMOTE_ADDR), and kernel-created requests carry no proxy chain, so
 *     each phase sets REMOTE_ADDR directly. The file-based route limiter
 *     (keyed "ip|METHOD|path") then gets a fresh key per phase and the
 *     probe never exhausts a shared budget. X-Forwarded-For is not used: it
 *     only applies once the proxy chain is trusted, which in-kernel it is not.
 *
 *   - State reset: the session Store and the auth guard are container
 *     singletons whose in-memory state (attributes, cached user) survives
 *     between $kernel->handle() calls. StartSession's loadSession() merges
 *     the handler's data into the previous request's attributes, so a
 *     signed-in session would bleed into the next phase (guest middleware
 *     then redirects GET /login, no form is rendered, the token comes back
 *     empty and every later POST dies with 419). After every request the
 *     probe therefore forgets the auth guards and flushes the session store.
 *
 * Usage: $env:DB_DATABASE='osas_beacon'; php scripts/login-flow-probe.php
 * Writes login_attempts and audit_log rows to the scratch database by design
 * (it exercises the real sign-in path); seeded accounts are cleared of stale
 * lockouts at start. Never run it against a production database.
 * Exit codes: 0 = all checks passed, 1 = at least one failure.
 */

use App\Enums\Role;
use App\Services\LoginThrottle;
use App\Support\RateLimiter;
use Illuminate\Http\Request;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Safety guard: this probe records failed logins, exhausts a route-throttle
// key and writes audit rows. Never run it against a production database.
if ((string) config('database.connections.pgsql.database') !== 'osas_beacon') {
    fwrite(STDERR, "login-flow-probe: set DB_DATABASE=osas_beacon (scratch) before running.\n");
    exit(1);
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$seed = json_decode((string) file_get_contents($root . '/database/data/seed.json'), true, 512, JSON_THROW_ON_ERROR);
$demoPassword = (string) ($seed['meta']['demoPassword'] ?? 'Beacon@2026');

$failures = [];

$check = static function (string $name, bool $ok, string $detail = '') use (&$failures): void {
    echo ($ok ? 'PASS ' : 'FAIL '), $name, $ok || $detail === '' ? '' : ' — ' . $detail, PHP_EOL;

    if (!$ok) {
        $failures[] = $name;
    }
};

/**
 * Send one request through the kernel, then reset the state that would
 * otherwise leak into the next in-process request.
 *
 * @param array<string, string> $data
 * @param array<string, string> $cookies
 * @return array{status: int, body: string, cookies: array<string, string>, location: string, token: string}
 */
$hit = static function (string $method, string $path, array $data = [], array $cookies = [], string $remoteAddr = '127.0.0.1') use ($kernel): array {
    $request = Request::create('http://localhost' . $path, $method, $data, $cookies);
    $request->server->set('REMOTE_ADDR', $remoteAddr);

    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);

    // Fresh-process semantics (see the file docblock): the guard would keep
    // serving the cached user and the session Store would keep the previous
    // request's attributes otherwise.
    app('auth')->forgetGuards();
    app('session')->driver()->flush();

    $setCookies = [];
    foreach ($response->headers->getCookies() as $cookie) {
        $setCookies[$cookie->getName()] = $cookie->getValue();
    }

    $body = (string) $response->getContent();
    $token = '';
    if (preg_match('/name="_token" value="([^"]+)"/', $body, $matches) === 1) {
        $token = $matches[1];
    }

    return [
        'status' => $response->getStatusCode(),
        'body' => $body,
        'cookies' => $setCookies,
        'location' => (string) $response->headers->get('Location'),
        'token' => $token,
    ];
};

/** Extract the flash toast text rendered after a redirect back to /login. */
$flash = static function (array $response): string {
    if (preg_match_all('/auth-signout-toast.*?<\/div>/s', $response['body'], $matches) === 0) {
        return '';
    }

    $text = '';
    foreach ($matches[0] as $toast) {
        $text .= ' ' . trim(preg_replace('/\s+/', ' ', strip_tags($toast)));
    }

    return trim($text);
};

/** One failed-login cycle: POST, then follow the redirect to read the flash. */
$failedLogin = static function (string $email, string $remoteAddr, array $cookies, string $token) use ($hit, $flash): array {
    $post = $hit('POST', '/login', ['_token' => $token, 'email' => $email, 'password' => 'wrong-password-0'], $cookies, $remoteAddr);
    $jar = array_merge($cookies, $post['cookies']);

    if ($post['status'] === 429) {
        return ['status' => 429, 'flash' => '', 'body' => $post['body'], 'cookies' => $jar, 'token' => $token];
    }

    $page = $hit('GET', '/login', [], $jar, $remoteAddr);
    $jar = array_merge($jar, $page['cookies']);

    return ['status' => $post['status'], 'flash' => $flash($page), 'body' => $page['body'], 'cookies' => $jar, 'token' => $page['token']];
};

// ---------------------------------------------------------------------------
// Setup: one REMOTE_ADDR per phase; clear any rate-limit state an earlier
// run left behind (the limiter is file-based, keyed "ip|METHOD|path") and
// clear stale per-account lockouts on the seeded emails so re-runs are
// deterministic.
// ---------------------------------------------------------------------------

$ipBase = random_int(2, 200);
$ips = [
    'login' => '198.51.100.' . $ipBase,
    'lock' => '198.51.100.' . ($ipBase + 1),
    'throttle' => '198.51.100.' . ($ipBase + 2),
    'fresh' => '198.51.100.' . ($ipBase + 3),
];

foreach ($ips as $ip) {
    RateLimiter::clear($ip . '|POST|login');
    RateLimiter::clear($ip . '|GET|login');
}

$accounts = [
    [Role::Coordinator, 'josefina.reyes@uc.edu.ph'],
    [Role::Student, 'ryan.delacruz@uc.edu.ph'],
    [Role::Administrator, 'technical.admin@uc.edu.ph'],
];

foreach ($accounts as [, $email]) {
    LoginThrottle::clear($email);
}

// ---------------------------------------------------------------------------
// 1. Successful sign-in for every seeded role.
// ---------------------------------------------------------------------------

foreach ($accounts as [$role, $email]) {
    $form = $hit('GET', '/login', [], [], $ips['login']);
    $jar = $form['cookies'];

    $post = $hit('POST', '/login', ['_token' => $form['token'], 'email' => $email, 'password' => $demoPassword], $jar, $ips['login']);
    $jar = array_merge($jar, $post['cookies']);

    $locationPath = parse_url($post['location'], PHP_URL_PATH) ?: '';
    $check(
        'login.' . strtolower($role->value) . '.redirect',
        $post['status'] === 302 && $locationPath === $role->homePath(),
        "status={$post['status']} location={$post['location']}",
    );

    $dash = $hit('GET', $locationPath === '' ? '/' : $locationPath, [], $jar, $ips['login']);
    $title = preg_match('/<title>(.*?)<\/title>/s', $dash['body'], $m) === 1 ? trim(preg_replace('/\s+/', ' ', $m[1])) : '';
    $check(
        'login.' . strtolower($role->value) . '.dashboard',
        $dash['status'] === 200 && !str_contains($title, 'Sign in to Portal'),
        "status={$dash['status']} title={$title}",
    );
}

// ---------------------------------------------------------------------------
// 2. Per-account lockout (fresh session, one REMOTE_ADDR key).
// ---------------------------------------------------------------------------

$lockEmail = 'login-flow-probe-' . bin2hex(random_bytes(3)) . '@example.invalid';
$form = $hit('GET', '/login', [], [], $ips['lock']);
$jar = $form['cookies'];
$token = $form['token'];

$wrongFlashes = [];

for ($attempt = 1; $attempt <= 5; $attempt++) {
    $result = $failedLogin($lockEmail, $ips['lock'], $jar, $token);
    $jar = $result['cookies'];
    $token = $result['token'];
    $wrongFlashes[] = $result['flash'];
}

$allRejected = count($wrongFlashes) === 5;

foreach ($wrongFlashes as $wrongFlash) {
    if (!str_contains($wrongFlash, 'Incorrect email or password')) {
        $allRejected = false;
    }
}

$check('lock.attempts-1-to-5-rejected', $allRejected, 'flashes=' . implode(' | ', $wrongFlashes));

// 6th attempt: the per-account counter is at 5 -> locked message, not a
// password check, even though the account does not exist.
$locked = $failedLogin($lockEmail, $ips['lock'], $jar, $token);
$jar = $locked['cookies'];
$token = $locked['token'];
$check(
    'lock.6th-attempt-account-locked',
    $locked['status'] === 302 && str_contains($locked['flash'], 'Too many failed attempts'),
    "status={$locked['status']} flash={$locked['flash']}",
);

// Isolation: another account from the SAME ip is unaffected (per-account key).
$other = $failedLogin('login-flow-probe-other-' . bin2hex(random_bytes(2)) . '@example.invalid', $ips['lock'], $jar, $token);
$check(
    'lock.other-account-unaffected',
    $other['status'] === 302 && str_contains($other['flash'], 'Incorrect email or password'),
    "status={$other['status']} flash={$other['flash']}",
);

// ---------------------------------------------------------------------------
// 3. Route throttle: 21st POST /login from one IP -> branded 429.
// ---------------------------------------------------------------------------

$form = $hit('GET', '/login', [], [], $ips['throttle']);
$jar = $form['cookies'];
$token = $form['token'];

$statuses = [];
$brandedBody = '';

for ($post = 1; $post <= 21; $post++) {
    $result = $failedLogin('throttle-probe-' . bin2hex(random_bytes(3)) . '@example.invalid', $ips['throttle'], $jar, $token);
    $jar = $result['cookies'];
    $token = $result['token'];
    $statuses[] = $result['status'];

    if ($result['status'] === 429) {
        $brandedBody = $result['body'];
    }
}

$check(
    'throttle.20-posts-within-limit',
    count(array_filter($statuses, static fn (int $s): bool => $s === 302)) === 20,
    'statuses=' . implode(',', $statuses),
);
$check('throttle.21st-post-429', ($statuses[20] ?? 0) === 429, 'status=' . ($statuses[20] ?? 'missing'));
$check('throttle.429-branding', str_contains($brandedBody, 'Too Many Requests'));

// A GET from the throttled IP still works: the key includes the method, so
// the POST flood never locks the account out of loading the sign-in form.
$stillLoads = $hit('GET', '/login', [], $jar, $ips['throttle']);
$check('throttle.get-still-served', $stillLoads['status'] === 200, 'status=' . $stillLoads['status']);

// Isolation: a different IP still passes the route throttle immediately
// (fresh key), and a successful sign-in still works end to end after all
// the failure traffic above.
$form = $hit('GET', '/login', [], [], $ips['fresh']);
$jar = $form['cookies'];
$okPost = $hit('POST', '/login', ['_token' => $form['token'], 'email' => 'technical.admin@uc.edu.ph', 'password' => $demoPassword], $jar, $ips['fresh']);
$freshPath = parse_url($okPost['location'], PHP_URL_PATH) ?: '';
$check(
    'throttle.other-ip-unaffected',
    $okPost['status'] === 302 && $freshPath === Role::Administrator->homePath(),
    "status={$okPost['status']} location={$okPost['location']}",
);

// ---------------------------------------------------------------------------

echo PHP_EOL;

if ($failures === []) {
    echo 'ALL LOGIN FLOW CHECKS PASSED', PHP_EOL;
    exit(0);
}

echo count($failures), ' FAILURES:', PHP_EOL;

foreach ($failures as $failure) {
    echo '  - ', $failure, PHP_EOL;
}

exit(1);
