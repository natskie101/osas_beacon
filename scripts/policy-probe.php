<?php

declare(strict_types=1);

/**
 * Authorization probe for UserPolicy role management.
 *
 * Asserts the role-assignment matrix that closes the Coordinator →
 * Administrator privilege escalation, plus the surrounding change_role /
 * manage rules and the exact gate UserController::update() applies.
 * Pure in-memory policy checks — the database is never touched.
 *
 * Usage: php scripts/policy-probe.php
 * Exit codes: 0 = all checks passed, 1 = at least one failure.
 */

use App\Policies\UserPolicy;
use App\Support\Exceptions\ForbiddenHttpException;

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

// Actor/target fixtures (plain arrays — the policy never queries the DB).
$coordinator = ['id' => 2, 'role' => 'Coordinator', 'status' => 'Active'];
$administrator = ['id' => 1, 'role' => 'Administrator', 'status' => 'Active'];
$student = ['id' => 3, 'role' => 'Student', 'status' => 'Active'];

// 1. Assignable-role matrix — the escalation fix.
$check(
    'coordinator assignable roles = [Student]',
    UserPolicy::assignableRoles($coordinator) === ['Student'],
    implode(',', UserPolicy::assignableRoles($coordinator)),
);
$check(
    'administrator assignable roles include Administrator',
    UserPolicy::assignableRoles($administrator) === ['Student', 'Coordinator', 'Administrator'],
    implode(',', UserPolicy::assignableRoles($administrator)),
);
$check('guest assignable roles = []', UserPolicy::assignableRoles(null) === []);

// 2. The vulnerability itself: a Coordinator must not grant a staff role.
$check('coordinator cannot assign Administrator', !UserPolicy::canAssignRole($coordinator, 'Administrator'));
$check('coordinator cannot assign Coordinator', !UserPolicy::canAssignRole($coordinator, 'Coordinator'));
$check('coordinator can assign Student', UserPolicy::canAssignRole($coordinator, 'Student'));
$check('administrator can assign Administrator', UserPolicy::canAssignRole($administrator, 'Administrator'));
$check('administrator can assign Coordinator', UserPolicy::canAssignRole($administrator, 'Coordinator'));

// 3. Creation rules are unchanged.
$check('coordinator cannot create Administrator', !UserPolicy::canCreateRole($coordinator, 'Administrator'));
$check('coordinator cannot create Coordinator', !UserPolicy::canCreateRole($coordinator, 'Coordinator'));
$check('coordinator can create Student', UserPolicy::canCreateRole($coordinator, 'Student'));
$check('administrator can create Coordinator', UserPolicy::canCreateRole($administrator, 'Coordinator'));
$check('administrator cannot create Administrator', !UserPolicy::canCreateRole($administrator, 'Administrator'));

// 4. change_role gating per target role.
$check('coordinator can change role on Student', UserPolicy::allows('change_role', $coordinator, $student));
$check('coordinator cannot change role on Coordinator', !UserPolicy::allows('change_role', $coordinator, $coordinator));
$check('coordinator cannot change role on Administrator', !UserPolicy::allows('change_role', $coordinator, $administrator));
$check('administrator can change role on Student', UserPolicy::allows('change_role', $administrator, $student));
$check('administrator can change role on Coordinator', UserPolicy::allows('change_role', $administrator, $coordinator));
$check('administrator cannot change role on Administrator', !UserPolicy::allows('change_role', $administrator, $administrator));

// 5. Non-role management stays intact: coordinators may still edit fellow
//    coordinators' profile fields, while Administrator accounts stay
//    view-only for them.
$check('coordinator can manage Student', UserPolicy::allows('manage', $coordinator, $student));
$check('coordinator can manage fellow Coordinator', UserPolicy::allows('manage', $coordinator, $coordinator));
$check(
    'coordinator can only view Administrator',
    UserPolicy::allows('view', $coordinator, $administrator)
        && !UserPolicy::allows('manage', $coordinator, $administrator),
);

// 6. authorize() must throw for privileged attempts.
try {
    UserPolicy::authorize('change_role', $coordinator, $coordinator);
    $check('authorize throws: coordinator changing a coordinator role', false, 'no exception');
} catch (ForbiddenHttpException) {
    $check('authorize throws: coordinator changing a coordinator role', true);
}

try {
    UserPolicy::authorize('manage', $coordinator, $administrator);
    $check('authorize throws: coordinator managing an administrator', false, 'no exception');
} catch (ForbiddenHttpException) {
    $check('authorize throws: coordinator managing an administrator', true);
}

// 7. The exact gate UserController::update() applies: an unchanged role
//    round-trip passes without gates; a real change must clear both
//    authorize('change_role') and canAssignRole().
$gate = static function (array $actor, array $target, string $newRole): bool {
    if ($newRole === (string) $target['role']) {
        return true;
    }

    try {
        UserPolicy::authorize('change_role', $actor, $target);
    } catch (ForbiddenHttpException) {
        return false;
    }

    return UserPolicy::canAssignRole($actor, $newRole);
};

$check('gate: coordinator edits fellow coordinator (unchanged)', $gate($coordinator, $coordinator, 'Coordinator'));
$check('gate: coordinator edits student (unchanged)', $gate($coordinator, $student, 'Student'));
$check('gate: coordinator promotes student → Administrator blocked', !$gate($coordinator, $student, 'Administrator'));
$check('gate: coordinator promotes student → Coordinator blocked', !$gate($coordinator, $student, 'Coordinator'));
$check('gate: coordinator demotes fellow coordinator blocked', !$gate($coordinator, $coordinator, 'Student'));
$check('gate: administrator edits student (unchanged)', $gate($administrator, $student, 'Student'));
$check('gate: administrator promotes student → Administrator allowed', $gate($administrator, $student, 'Administrator'));
$check('gate: administrator promotes coordinator → Administrator allowed', $gate($administrator, $coordinator, 'Administrator'));

if ($failures === []) {
    echo PHP_EOL, 'All checks passed.', PHP_EOL;
    exit(0);
}

echo PHP_EOL, count($failures) . ' check(s) failed.', PHP_EOL;
exit(1);
