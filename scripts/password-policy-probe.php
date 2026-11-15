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
    'student_id' => 'PROBE-VAL-001', 'age' => '20',
    'email' => 'probe-validation@example.com',
    'program' => $programs[0] ?? '', 'phone' => '+639000000000',
    'year_level' => $years[0] ?? '',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new RegisterStudentRequest(), $registerPayload('short123'), 'register rejects 8-char password');
$rejects(new RegisterStudentRequest(), $registerPayload('short123456'), 'register rejects 11-char password');
$accepts(new RegisterStudentRequest(), $registerPayload('long-enough-12'), 'register accepts 12-char password');

$resetPayload = static fn (string $password): array => [
    'token' => 'a-token-value', 'email' => 'probe-validation@example.com',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new ResetPasswordRequest(), $resetPayload('short123'), 'reset rejects 8-char password');
$rejects(new ResetPasswordRequest(), $resetPayload('short123456'), 'reset rejects 11-char password');
$accepts(new ResetPasswordRequest(), $resetPayload('long-enough-12'), 'reset accepts 12-char password');

$storePayload = static fn (string $password): array => [
    'first_name' => 'Probe', 'last_name' => 'Probe',
    'email' => 'probe-validation@example.com', 'role' => 'Student', 'status' => 'Pending',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new StoreUserRequest(), $storePayload('short123'), 'admin create rejects 8-char password');
$rejects(new StoreUserRequest(), $storePayload('short123456'), 'admin create rejects 11-char password');
$accepts(new StoreUserRequest(), $storePayload('long-enough-12'), 'admin create accepts 12-char password');

$updatePayload = static fn (string $password): array => [
    'first_name' => 'Probe', 'last_name' => 'Probe',
    'email' => 'probe-validation@example.com', 'role' => 'Student', 'status' => 'Pending',
    'password' => $password, 'password_confirmation' => $password,
];

$rejects(new UpdateUserRequest(1), $updatePayload('short123'), 'admin edit rejects 8-char password');
$rejects(new UpdateUserRequest(1), $updatePayload('short123456'), 'admin edit rejects 11-char password');
$accepts(new UpdateUserRequest(1), $updatePayload('long-enough-12'), 'admin edit accepts 12-char password');

if ($failures === []) {
    echo PHP_EOL, 'All password policy checks passed.', PHP_EOL;
    exit(0);
}

echo PHP_EOL, count($failures) . ' check(s) failed.', PHP_EOL;
exit(1);
