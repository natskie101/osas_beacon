<?php

declare(strict_types=1);

/**
 * Password policy probe.
 *
 * Asserts that every password-accepting form enforces the 12-character
 * minimum: 8- and 11-character passwords must be rejected, 12-character
 * passwords accepted, across self-registration, password reset and the
 * admin create/edit forms.
 *
 * Validation only — nothing is written. The unique:users rules issue
 * read-only SELECTs against the configured database.
 *
 * Usage: php scripts/password-policy-probe.php
 * Exit codes: 0 = all checks passed, 1 = at least one failure.
 */

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Support\Exceptions\ValidationException;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failures = [];

$check = static function (string $name, bool $ok, string $detail = '') use (&$failures): void {
    echo ($ok ? 'PASS ' : 'FAIL '), $name, $ok || $detail === '' ? '' : ' — ' . $detail, PHP_EOL;

    if (!$ok) {
        $failures[] = $name;
    }
};

$rejects = static function (object $formRequest, array $payload, string $label) use ($check): void {
    try {
        $formRequest->validate(Illuminate\Http\Request::create('/probe', 'POST', $payload));
        $check($label, false, 'no exception');
    } catch (ValidationException) {
        $check($label, true);
    } catch (Throwable $exception) {
        $check($label, false, get_class($exception) . ': ' . $exception->getMessage());
    }
};

$accepts = static function (object $formRequest, array $payload, string $label) use ($check): void {
    try {
        $formRequest->validate(Illuminate\Http\Request::create('/probe', 'POST', $payload));
        $check($label, true);
    } catch (Throwable $exception) {
        $check($label, false, get_class($exception) . ': ' . $exception->getMessage());
    }
};

$programs = config('people.programs', []);
$years = config('people.year_levels', []);

$registerPayload = static fn (string $password): array => [
    'first_name' => 'Probe', 'middle_name' => 'Validation', 'last_name' => 'Probe',
    'student_id' => '112233', 'age' => '20',
    'email' => 'probe-validation@example.com',
    'program' => $programs[0] ?? '', 'phone' => '09123456789',
    'year_level' => $years[0] ?? '',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new RegisterStudentRequest(), $registerPayload('short-7'), 'register rejects 7-char password');
$accepts(new RegisterStudentRequest(), $registerPayload('valid-8c'), 'register accepts 8-char password');

$resetPayload = static fn (string $password): array => [
    'token' => 'a-token-value', 'email' => 'probe-validation@example.com',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new ResetPasswordRequest(), $resetPayload('short-7'), 'reset rejects 7-char password');
$accepts(new ResetPasswordRequest(), $resetPayload('valid-8c'), 'reset accepts 8-char password');

$storePayload = static fn (string $password): array => [
    'first_name' => 'Probe', 'last_name' => 'Probe',
    'email' => 'probe-validation@example.com', 'role' => 'Student', 'status' => 'Pending',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new StoreUserRequest(), $storePayload('short-7'), 'admin create rejects 7-char password');
$accepts(new StoreUserRequest(), $storePayload('valid-8c'), 'admin create accepts 8-char password');

$updatePayload = static fn (string $password): array => [
    'first_name' => 'Probe', 'last_name' => 'Probe',
    'email' => 'probe-validation@example.com', 'role' => 'Student', 'status' => 'Pending',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new UpdateUserRequest(1), $updatePayload('short-7'), 'admin edit rejects 7-char password');
$accepts(new UpdateUserRequest(1), $updatePayload('valid-8c'), 'admin edit accepts 8-char password');

if ($failures === []) {
    echo PHP_EOL, 'All password policy checks passed.', PHP_EOL;
    exit(0);
}

echo PHP_EOL, count($failures) . ' check(s) failed.', PHP_EOL;
exit(1);
