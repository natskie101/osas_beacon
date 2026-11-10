<?php

/**
 * Phase 4a HTTP integration suite.
 *
 * Runs ~80 black-box HTTP checks against a running server (php artisan serve
 * on 127.0.0.1:8000): guest pages, CSRF 419, validation 302 + flash toast,
 * coordinator/student page 200s, access-control 302s, branded 404s (App
 * exceptions), legacy redirects, logout + guard, and the branded 429 route.
 *
 * Usage:  php scripts/http-suite.php
 * Exit 0 = all checks passed.
 */

declare(strict_types=1);

$base = getenv('BASE_URL') ?: 'http://127.0.0.1:8000';
$jarA = sys_get_temp_dir() . '/beacon_suite_a.txt'; // guest -> coordinator
$jarB = sys_get_temp_dir() . '/beacon_suite_b.txt'; // student
@unlink($jarA);
@unlink($jarB);

$GLOBALS['pass'] = 0;
$GLOBALS['failures'] = [];

function check(string $name, bool $ok, string $detail = ''): void
{
    if ($ok) {
        $GLOBALS['pass']++;
        echo "  ok  {$name}\n";
    } else {
        $GLOBALS['failures'][] = $name . ($detail !== '' ? " — {$detail}" : '');
        echo "FAIL  {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

/**
 * @return array{status:int, body:string, location:string}
 */
function req(string $method, string $path, array $post = [], array $headers = [], string $jar = ''): array
{
    global $base;

    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_USERAGENT => 'BeaconHttpSuite/1.0',
    ]);

    if ($headers !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    $location = '';
    $hdrs = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    $rawHeaders = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // unused; parse via header function instead
    curl_close($ch);

    return [
        'status' => $status,
        'body' => $body === false ? '' : $body,
        'location' => $location,
    ];
}

/** Same as req() but captures the Location header. */
function reqL(string $method, string $path, array $post = [], array $headers = [], string $jar = ''): array
{
    global $base;

    $location = '';
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_USERAGENT => 'BeaconHttpSuite/1.0',
        CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$location) {
            $len = strlen($header);
            $line = trim($header);
            if (stripos($line, 'location:') === 0) {
                $location = trim(substr($line, 9));
            }
            return $len;
        },
    ]);

    if ($headers !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    // With CURLOPT_HEADER=true the body follows the header block.
    $parts = explode("\r\n\r\n", (string) $raw, 2);
    $body = $parts[1] ?? '';

    return ['status' => $status, 'body' => $body, 'location' => $location];
}

function csrfToken(string $jar, string $path = '/login'): string
{
    $r = req('GET', $path, [], [], $jar);
    if ($r['status'] !== 200 || !preg_match('/name="_token"\s+value="([^"]+)"/', $r['body'], $m)) {
        return '';
    }
    return $m[1];
}

function expectStatus(array $r, int $want): bool
{
    return $r['status'] === $want;
}

function locationHas(array $r, string $needle): bool
{
    return str_contains($r['location'], $needle);
}

function bodyHas(array $r, string $needle): bool
{
    return str_contains($r['body'], $needle);
}

echo "== Phase 4a HTTP suite ({$base}) ==\n";

/* ---------- Guest phase (jar A) ---------- */
echo "\n-- guest --\n";

$r = reqL('GET', '/');
check('guest GET / -> 302 to /login', expectStatus($r, 302) && locationHas($r, '/login'),
    "got {$r['status']} loc={$r['location']}");

$r = req('GET', '/login');
check('GET /login -> 200 + csrf token', expectStatus($r, 200) && bodyHas($r, 'name="_token"'), 'got ' . $r['status']);

$r = req('GET', '/create-account');
check('GET /create-account -> 200', expectStatus($r, 200), 'got ' . $r['status']);

$r = req('GET', '/register');
check('GET /register -> 200', expectStatus($r, 200), 'got ' . $r['status']);

$r = req('GET', '/forgot-password');
check('GET /forgot-password -> 200', expectStatus($r, 200), 'got ' . $r['status']);

$r = reqL('GET', '/reset-password/bogus-token');
check('GET /reset-password/bogus-token -> 302 to /forgot-password',
    expectStatus($r, 302) && locationHas($r, '/forgot-password'),
    "got {$r['status']} loc={$r['location']}");

$r = reqL('POST', '/login', [], [], $jarA);
check('POST /login without CSRF -> 419', expectStatus($r, 419), 'got ' . $r['status']);

$token = csrfToken($jarA);
check('login page yields CSRF token', $token !== '');

$r = reqL('POST', '/login', ['_token' => $token, 'email' => 'nosuchuser@example.com', 'password' => 'WrongPass!1'], [], $jarA);
check('POST /login bad credentials -> 302 to /login',
    expectStatus($r, 302) && locationHas($r, '/login'),
    "got {$r['status']} loc={$r['location']}");

$r = req('GET', '/login', [], [], $jarA);
check('failed login shows error toast', bodyHas($r, 'auth-signout-toast--error'));

$r = reqL('POST', '/login', ['_token' => $token, 'email' => 'nosuchuser@example.com'], ['Referer: ' . $base . '/login'], $jarA);
check('POST /login missing password -> 302 back with validation errors',
    expectStatus($r, 302) && locationHas($r, '/login'),
    "got {$r['status']} loc={$r['location']}");

$r = req('GET', '/login', [], [], $jarA);
check('validation failure shows error toast', bodyHas($r, 'auth-signout-toast--error'));

$r = reqL('GET', '/admin');
check('guest GET /admin -> 302 to /login', expectStatus($r, 302) && locationHas($r, '/login'),
    "got {$r['status']} loc={$r['location']}");

$r = reqL('GET', '/student');
check('guest GET /student -> 302 to /login', expectStatus($r, 302) && locationHas($r, '/login'),
    "got {$r['status']} loc={$r['location']}");

$r = reqL('GET', '/index.html');
check('GET /index.html -> 302 legacy redirect', expectStatus($r, 302), 'got ' . $r['status']);

$r = req('GET', '/up');
check('GET /up (health) -> 200', expectStatus($r, 200), 'got ' . $r['status']);

$r = req('GET', '/definitely-missing-page-xyz');
check('GET unknown URL -> 404', expectStatus($r, 404), 'got ' . $r['status']);

$r = req('POST', '/');
check('POST / (GET-only route) -> 405', expectStatus($r, 405), 'got ' . $r['status']);

/* ---------- Coordinator phase (jar A) ---------- */
echo "\n-- coordinator --\n";

$token = csrfToken($jarA);
$r = reqL('POST', '/login', ['_token' => $token, 'email' => 'josefina.reyes@uc.edu.ph', 'password' => 'Beacon@2026'], [], $jarA);
check('coordinator login -> 302 to /admin',
    expectStatus($r, 302) && locationHas($r, '/admin'),
    "got {$r['status']} loc={$r['location']}");

$coordPages = [
    '/admin',
    '/admin/users',
    '/admin/users/create',
    '/admin/users/USR-1001',
    '/admin/users/USR-1001/edit',
    '/admin/support',
    '/admin/activity',
    '/admin/announcements',
    '/admin/announcements/create',
    '/admin/regulations',
    '/admin/regulations/create',
    '/admin/orientation',
    '/admin/orientation/create',
    '/admin/orientation/ORIENT-2026-001',
    '/admin/orientation/ORIENT-2026-001/edit',
    '/admin/orientation/ORIENT-2026-001/quiz',
    '/admin/events',
    '/admin/events/create',
    '/admin/lost-found',
    '/admin/lost-found/create',
    '/admin/lost-found/export',
    '/admin/document-requests',
    '/admin/analytics',
    '/admin/reports',
    '/admin/broadcasts',
    '/admin/broadcasts/create',
    '/admin/reports/orientation/ORIENT-2026-001',
    '/admin/reports/orientation/ORIENT-2026-001/csv',
    '/admin/reports/orientation/ORIENT-2026-001/excel',
    '/profile',
    '/notifications',
];

foreach ($coordPages as $path) {
    $r = req('GET', $path, [], [], $jarA);
    check("GET {$path} -> 200", expectStatus($r, 200), 'got ' . $r['status']);
}

$r = req('GET', '/admin/users/USR-9999', [], [], $jarA);
check('GET missing user -> branded 404', expectStatus($r, 404) && bodyHas($r, 'Page Not Found'),
    'got ' . $r['status']);

$r = req('GET', '/coordinator/not-a-page', [], [], $jarA);
check('GET /coordinator/not-a-page -> branded 404', expectStatus($r, 404) && bodyHas($r, 'BEACON OSAS Portal'),
    'got ' . $r['status']);

$r = reqL('GET', '/coordinator/dashboard.html', [], [], $jarA);
check('GET /coordinator/dashboard.html -> 302 to /admin/dashboard',
    expectStatus($r, 302) && locationHas($r, '/admin/dashboard'),
    "got {$r['status']} loc={$r['location']}");

$r = reqL('GET', '/admin/system-overview', [], [], $jarA);
check('coordinator GET /admin/system-overview (Administrator only) -> 302 to /login',
    expectStatus($r, 302) && locationHas($r, '/login'), "got {$r['status']} loc={$r['location']}");

$r = reqL('GET', '/student', [], [], $jarA);
check('coordinator GET /student -> 302 to /login (role denial)',
    expectStatus($r, 302) && locationHas($r, '/login'), "got {$r['status']} loc={$r['location']}");

$token = '';
preg_match('/name="_token"\s+value="([^"]+)"/', req('GET', '/profile', [], [], $jarA)['body'], $m);
$token = $m[1] ?? '';
$r = reqL('POST', '/logout', [], [], $jarA);
check('POST /logout without CSRF -> 419', expectStatus($r, 419), 'got ' . $r['status']);

$r = reqL('POST', '/logout', ['_token' => $token], [], $jarA);
check('POST /logout -> 302 to /login', expectStatus($r, 302) && locationHas($r, '/login'),
    "got {$r['status']} loc={$r['location']}");

$r = reqL('GET', '/admin', [], [], $jarA);
check('after logout GET /admin -> 302 to /login (guard)', expectStatus($r, 302) && locationHas($r, '/login'),
    "got {$r['status']} loc={$r['location']}");

/* ---------- Student phase (jar B) ---------- */
echo "\n-- student --\n";

$token = csrfToken($jarB);
$r = reqL('POST', '/login', ['_token' => $token, 'email' => 'ryan.delacruz@uc.edu.ph', 'password' => 'Beacon@2026'], [], $jarB);
check('student login -> 302 to /student',
    expectStatus($r, 302) && locationHas($r, '/student'),
    "got {$r['status']} loc={$r['location']}");

$studentPages = [
    '/student',
    '/student/announcements',
    '/student/rules',
    '/student/orientation',
    '/student/orientation/ORIENT-2026-001',
    '/student/events',
    '/student/lost-found',
    '/student/lost-found/report',
    '/student/document-requests',
    '/student/document-requests/create',
    '/student/support',
    '/student/support/create',
    '/profile',
    '/notifications',
];

foreach ($studentPages as $path) {
    $r = req('GET', $path, [], [], $jarB);
    check("GET {$path} -> 200", expectStatus($r, 200), 'got ' . $r['status']);
}

$r = req('GET', '/student/orientation/ORIENT-2026-001/quiz', [], [], $jarB);
check('GET student quiz (no published assessment) -> branded 404 "not available"',
    expectStatus($r, 404) && bodyHas($r, 'Page Not Found') && bodyHas($r, 'not available'),
    'got ' . $r['status']);

$r = reqL('GET', '/admin', [], [], $jarB);
check('student GET /admin -> 302 to /login (role denial)',
    expectStatus($r, 302) && locationHas($r, '/login'), "got {$r['status']} loc={$r['location']}");

$r = reqL('GET', '/admin/users', [], [], $jarB);
check('student GET /admin/users -> 302 to /login (role denial)',
    expectStatus($r, 302) && locationHas($r, '/login'), "got {$r['status']} loc={$r['location']}");

$r = reqL('GET', '/login', [], [], $jarB);
check('authenticated GET /login -> 302 to /student (guest middleware)',
    expectStatus($r, 302) && locationHas($r, '/student'),
    "got {$r['status']} loc={$r['location']}");

$r = req('GET', '/student/no-such-page', [], [], $jarB);
check('GET /student/no-such-page -> branded 404',
    expectStatus($r, 404) && bodyHas($r, 'Page Not Found') && bodyHas($r, 'BEACON OSAS Portal'),
    'got ' . $r['status']);

$token = '';
preg_match('/name="_token"\s+value="([^"]+)"/', req('GET', '/student/support/create', [], [], $jarB)['body'], $m);
$token = $m[1] ?? '';

$r = reqL('POST', '/student/support', [], [], $jarB);
check('POST /student/support without CSRF -> 419', expectStatus($r, 419), 'got ' . $r['status']);

$r = reqL('POST', '/student/support', ['_token' => $token], ['Referer: ' . $base . '/student/support/create'], $jarB);
check('POST /student/support missing fields -> 302 back with validation errors',
    expectStatus($r, 302) && locationHas($r, '/student/support'),
    "got {$r['status']} loc={$r['location']}");

$r = req('GET', '/student/support/create', [], [], $jarB);
check('validation failure shows error state', bodyHas($r, 'toast') || bodyHas($r, 'error') || bodyHas($r, 'invalid'),
    'no error marker in body');

/* ---------- Rate limit (branded 429), run last ---------- */
echo "\n-- throttle --\n";

// Grab a CSRF token (forgot-password form) for the throttled POSTs below.
$fp = req('GET', '/forgot-password', [], [], $jarB);
preg_match('/name="_token"\s+value="([^"]+)"/', $fp['body'], $m);
$token = $m[1] ?? '';

$last = null;
for ($i = 0; $i < 11; $i++) {
    $last = reqL('POST', '/reset-password', [
        '_token' => $token,
        'token' => 'bogus',
        'email' => 'nobody@example.com',
        'password' => 'NewPass123!',
        'password_confirmation' => 'NewPass123!',
    ], [], $jarB);
}
check('11th POST /reset-password -> 429 branded "Too Many Requests"',
    $last !== null && expectStatus($last, 429) && bodyHas($last, 'Too Many Requests'),
    'got ' . ($last['status'] ?? 'null'));

/* ---------- Summary ---------- */
echo "\n== Result: {$GLOBALS['pass']} passed, " . count($GLOBALS['failures']) . " failed ==\n";
foreach ($GLOBALS['failures'] as $f) {
    echo "  FAILED: {$f}\n";
}
exit($GLOBALS['failures'] === [] ? 0 : 1);
