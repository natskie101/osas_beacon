<?php

declare(strict_types=1);

use App\Support\Exceptions\HttpException;
use App\Support\Exceptions\ValidationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // TEMPORARY diagnostics (removed once the Render 500 is fixed):
        // registered OUTSIDE the 'web' group so StartSession (the crashing
        // middleware) never runs, and gated by a token so it is unusable
        // without it. Reports the real DB error Render's instance hits.
        then: function () {
            Route::get('/__diag', function (Request $request) {
                if (!hash_equals('bcn-diag-2026', (string) $request->query('k', ''))) {
                    abort(404);
                }

                $lines = [];
                $probe = function (string $label, callable $fn) use (&$lines) {
                    try {
                        $result = $fn();
                        $lines[] = "$label: OK" . ($result !== null && $result !== '' ? ' -> ' . json_encode($result) : '');
                    } catch (\Throwable $e) {
                        $lines[] = "$label: FAIL [" . get_class($e) . '] ' . $e->getMessage();
                    }
                };

                $probe('php', fn() => PHP_VERSION);
                $probe('app.env/debug', fn() => [config('app.env'), (bool) config('app.debug')]);
                $probe('config.cached', fn() => file_exists(base_path('bootstrap/cache/config.php')));
                $probe('.env.present', fn() => file_exists(base_path('.env')));
                $probe('db.default', fn() => config('database.default'));

                $conn = config('database.connections.'.config('database.default'));
                $probe('db.config', function () use ($conn) {
                    return [
                        'host' => $conn['host'] ?? null,
                        'port' => $conn['port'] ?? null,
                        'database' => $conn['database'] ?? null,
                        'username' => $conn['username'] ?? null,
                        'sslmode' => $conn['sslmode'] ?? null,
                        'prefix' => $conn['prefix'] ?? '',
                        'password_set' => !empty($conn['password']),
                    ];
                });

                $probe('session.config', fn() => [
                    'driver' => config('session.driver'),
                    'connection' => config('session.connection'),
                    'table' => config('session.table'),
                ]);

                $probe('pdo.connect', fn() => DB::connection()->getPdo() !== null);
                $probe('q.current_db', fn() => DB::select('select current_database() as db, version() as ver'));
                $probe('q.sessions.exists', fn() => ['has' => Schema::hasTable('sessions')]);
                $probe('q.sessions.count', fn() => ['rows' => DB::table('sessions')->count()]);
                $probe('q.session.read (the crashing query)',
                    fn() => DB::table('sessions')->find('mcYwiNSq2k6kzbrDIAG'));
                $probe('q.users.count', fn() => ['rows' => DB::table('users')->count()]);
                $probe('tx.begin/select', function () {
                    DB::beginTransaction();
                    DB::select('select 1 as ok');
                    DB::commit();
                    return 'committed';
                });

                $probe('migrations', fn() => ['ran' => DB::table('migrations')->count()]);

                return response(implode("\n", $lines)."\n", 200)
                    ->header('Content-Type', 'text/plain')
                    ->header('Cache-Control', 'no-store');
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'throttle' => \App\Http\Middleware\ThrottleRequests::class,
        ]);

        $middleware->validateCsrfTokens();

        // Render terminates TLS at its proxy; trust it so Laravel sees HTTPS
        // (secure cookies, correct https:// URL generation).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The portal's legacy exceptions predate the native kernel (they were
        // caught by the legacy router). Reproduce that behaviour here so
        // validation failures flash errors + old input and redirect back
        // instead of bubbling up as 500s.
        $exceptions->render(function (ValidationException $e) {
            session()->flash('errors', $e->errors());
            session()->flashInput($e->old());

            return redirect()->back();
        });

        // Portal HTTP errors (404/403/405/429) render the branded error page
        // with the exception's real status code, as the old Router did.
        $exceptions->render(function (HttpException $e) {
            $response = response()->view('errors/error', [
                'status' => $e->statusCode,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'The request could not be completed.',
                'debug' => (bool) config('app.debug', false),
                'exception' => $e,
            ], $e->statusCode);

            foreach ($e->headers as $name => $value) {
                $response->headers->set($name, $value);
            }

            return $response;
        });
    })->create();
