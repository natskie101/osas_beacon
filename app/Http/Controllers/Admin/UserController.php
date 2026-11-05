<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Notification;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\FileStorage;
use App\Services\UserService;
use App\Support\Auth;
use App\Support\DB;
use App\Support\Exceptions\ForbiddenHttpException;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use RuntimeException;

final class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
<<<<<<< HEAD
        $tab = $request->string('tab', 'directory') === 'queue' ? 'queue' : 'directory';
        $status = $request->string('status');

        if ($tab === 'queue') {
            $status = 'Pending';
        } elseif ($status === 'Pending') {
            $status = '';
        }

        $filters = [
            'search' => $request->string('search'),
            'role' => $request->string('role'),
            'status' => $status,
            'group' => $tab === 'queue' ? '' : $request->string('group'),
            'sort' => $request->string('sort', 'name'),
            'direction' => $request->string('direction', 'asc'),
            'tab' => $tab,
            'exclude_pending' => $tab === 'directory' && $status === '',
=======
        $filters = [
            'search' => $request->string('search'),
            'role' => $request->string('role'),
            'status' => $request->string('status'),
            'group' => $request->string('group'),
            'sort' => $request->string('sort', 'name'),
            'direction' => $request->string('direction', 'asc'),
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        ];

        $pagination = User::paginate($filters, max(1, (int) $request->input('page', 1)), 10);

        return $this->page('layouts.app', 'admin/users/index', [
            'title' => 'User Account & Access Management',
            'pageTitle' => 'User Account & Access Management',
<<<<<<< HEAD
            'pageSubtitle' => 'Verify pending registrations and manage portal access and system roles.',
=======
            'pageSubtitle' => 'Manage portal access and system roles for all students and staff.',
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
            'pageKey' => 'users',
            'user' => $actor,
            'role' => $actor['role'],
            'accounts' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'counts' => User::counts(),
            'canCreate' => UserPolicy::canManageAccounts($actor),
<<<<<<< HEAD
            'tab' => $tab,
=======
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        ]);
    }

    public function show(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('view', $actor, $target);

        $schoolFile = !empty($target['school_id_file_id']) ? File::find((int) $target['school_id_file_id']) : null;

        return $this->page('layouts.app', 'admin/users/show', [
            'title' => User::fullName($target),
            'pageTitle' => User::fullName($target),
            'pageSubtitle' => $target['public_id'] . ' · ' . $target['role'] . ' account',
            'pageKey' => 'users',
            'user' => $actor,
            'role' => $actor['role'],
            'target' => $target,
            'schoolFile' => $schoolFile,
            'activity' => AuditLog::forUser((int) $target['id'], 20),
            'notifications' => Notification::forUser((int) $target['id'], 5),
            'canManage' => UserPolicy::allows('manage', $actor, $target),
            'canVerify' => UserPolicy::allows('verify', $actor, $target),
            'canReset' => UserPolicy::allows('reset_password', $actor, $target),
            'canUpload' => UserPolicy::allows('upload_school_id', $actor, $target),
            'canChangeRole' => UserPolicy::allows('change_role', $actor, $target),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = Auth::user();

        if (!UserPolicy::canManageAccounts($actor)) {
            throw new ForbiddenHttpException();
        }

        return $this->form($actor, null);
    }

    public function edit(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('manage', $actor, $target);

        return $this->form($actor, $target);
    }

    public function store(Request $request): Response
    {
        $actor = Auth::user();

        if (!UserPolicy::canManageAccounts($actor)) {
            throw new ForbiddenHttpException();
        }

        $data = (new StoreUserRequest())->validate($request);

        if (!UserPolicy::canAssignRole($actor, (string) $data['role'])) {
            throw new ForbiddenHttpException();
        }

        try {
            $target = UserService::create($data, $this->schoolIdFile($request), $actor);
        } catch (RuntimeException $exception) {
            throw new ValidationException(['school_id' => $exception->getMessage()], $request->all());
        }

        Session::flash('status', 'Account created for ' . User::fullName($target) . '.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function update(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('manage', $actor, $target);

        $data = (new UpdateUserRequest((int) $target['id']))->validate($request);

        if ((string) $data['role'] !== (string) $target['role']) {
            UserPolicy::authorize('change_role', $actor, $target);
        }

        if (!UserPolicy::canAssignRole($actor, (string) $data['role'])) {
            throw new ForbiddenHttpException();
        }

        $this->preventSelfPrivilegeChange($actor, $target, $data);
        $this->preventLastCoordinatorLockout($target, $data);

        try {
            $target = UserService::update($target, $data, $this->schoolIdFile($request), $actor);
        } catch (RuntimeException $exception) {
            throw new ValidationException(['school_id' => $exception->getMessage()], $request->all());
        }

        Session::flash('status', 'Account details saved for ' . User::fullName($target) . '.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function verify(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('verify', $actor, $target);

        UserService::setStatus(
            $target,
            'Active',
            $actor,
            'user.verified',
            'Account Verified',
            'Your account has been verified. You can now sign in to the portal.',
            'green',
        );

        Session::flash('status', User::fullName($target) . ' has been verified and activated.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function reject(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('verify', $actor, $target);
        $reason = trim($request->string('reason'));

        if ($reason === '') {
            $reason = 'The information submitted could not be verified.';
        }

        if (mb_strlen($reason) > 500) {
            $reason = mb_substr($reason, 0, 500);
        }

        UserService::setStatus(
            $target,
            'Inactive',
            $actor,
            'user.rejected',
            'Verification Rejected',
            'Your account verification was rejected. Reason: ' . $reason,
            'red',
        );

        Session::flash('status', User::fullName($target) . ' was rejected and deactivated.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function activate(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('manage', $actor, $target);
        $this->preventSelfStatusChange($actor, $target);

        UserService::setStatus(
            $target,
            'Active',
            $actor,
            'user.activated',
            'Account Reactivated',
            'Your portal account has been reactivated. You can sign in again.',
            'green',
        );

        Session::flash('status', User::fullName($target) . ' is active again.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function deactivate(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('manage', $actor, $target);
        $this->preventSelfStatusChange($actor, $target);

        if ($target['role'] === 'Coordinator' && (int) DB::value('SELECT COUNT(*) FROM users WHERE role = "Coordinator" AND status = "Active"') <= 1) {
            Session::flash('error', 'The last active coordinator cannot be deactivated.');

            return Response::redirect(url('/admin/users/' . $target['public_id']));
        }

        UserService::setStatus(
            $target,
            'Inactive',
            $actor,
            'user.deactivated',
            'Account Deactivated',
            'Your portal account has been deactivated. Contact OSAS Support if you believe this was a mistake.',
            'red',
        );

        Session::flash('status', User::fullName($target) . ' has been deactivated.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function resetPassword(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('reset_password', $actor, $target);
        UserService::sendPasswordReset($target, $actor);

        Session::flash('status', 'A password reset link was generated for ' . $target['email'] . '. In local mode it is written to storage/logs/mail.log.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function uploadSchoolId(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('upload_school_id', $actor, $target);
        $file = $this->schoolIdFile($request);

        if ($file === null) {
            Session::flash('error', 'Choose a school ID file to upload.');

            return Response::redirect(url('/admin/users/' . $target['public_id']));
        }

        try {
            UserService::attachSchoolId($target, $file, $actor);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());

            return Response::redirect(url('/admin/users/' . $target['public_id']));
        }

        Session::flash('status', 'School ID uploaded for ' . User::fullName($target) . '.');

        return Response::redirect(url('/admin/users/' . $target['public_id']));
    }

    public function downloadSchoolId(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $target = $this->find($publicId);
        UserPolicy::authorize('download_school_id', $actor, $target);

        if (empty($target['school_id_file_id'])) {
            throw new NotFoundHttpException('No school ID is attached to this account.');
        }

        $file = File::find((int) $target['school_id_file_id']);

        if ($file === null) {
            throw new NotFoundHttpException('No school ID is attached to this account.');
        }

        return FileStorage::download($file);
    }

    /** @param array<string, mixed> $actor @param array<string, mixed>|null $target */
    private function form(array $actor, ?array $target): Response
    {
        $isEdit = $target !== null;
        $old = Session::getFlash('old', []);
        $values = $isEdit ? array_merge($target, is_array($old) ? $old : []) : array_merge([
            'first_name' => '',
            'middle_name' => '',
            'last_name' => '',
            'email' => '',
            'username' => '',
            'role' => 'Student',
            'student_id' => '',
            'age' => '',
            'department' => '',
            'program' => '',
            'year_level' => '',
            'access_level' => config('people.access_levels.Student'),
            'account_tier' => 'Student Tier',
            'status' => 'Pending',
            'phone' => '',
        ], is_array($old) ? $old : []);

        $values['password'] = '';
        $values['password_confirmation'] = '';

        return $this->page('layouts.app', 'admin/users/form', [
            'title' => $isEdit ? 'Edit Account' : 'Add New Account',
            'pageTitle' => $isEdit ? 'Edit Account' : 'Add New Account',
            'pageSubtitle' => $isEdit
                ? 'Update the profile, access level and account status for ' . User::fullName($target) . '.'
                : 'Create a student or staff account and decide whether it starts active or pending verification.',
            'pageKey' => 'users',
            'user' => $actor,
            'role' => $actor['role'],
            'target' => $target,
            'values' => $values,
            'errors' => Session::errors(),
            'roles' => UserPolicy::assignableRoles($actor),
            'canChangeRole' => $isEdit ? UserPolicy::allows('change_role', $actor, $target) : true,
            'departments' => config('people.departments', []),
            'programs' => config('people.programs', []),
            'yearLevels' => config('people.year_levels', []),
            'accountTiers' => config('people.account_tiers', []),
            'statuses' => config('people.statuses', []),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function schoolIdFile(Request $request): ?array
    {
        $file = $request->file('school_id');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    /** @return array<string, mixed> */
    private function find(string $publicId): array
    {
        $target = User::findByPublicId($publicId);

        if ($target === null) {
            throw new NotFoundHttpException('The requested account could not be found.');
        }

        return $target;
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $target */
    private function preventSelfStatusChange(array $actor, array $target): void
    {
        if ((int) $actor['id'] === (int) $target['id']) {
            throw new ForbiddenHttpException('You cannot change your own account status.');
        }
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $target
     * @param array<string, mixed> $data
     */
    private function preventSelfPrivilegeChange(array $actor, array $target, array $data): void
    {
        if ((int) $actor['id'] !== (int) $target['id']) {
            return;
        }

        if ((string) $data['status'] !== (string) $target['status']) {
            throw new ForbiddenHttpException('You cannot change your own account status.');
        }

        if ((string) $data['role'] !== (string) $target['role']) {
            throw new ForbiddenHttpException('You cannot change your own role.');
        }
    }

    /** @param array<string, mixed> $target @param array<string, mixed> $data */
    private function preventLastCoordinatorLockout(array $target, array $data): void
    {
        if ($target['role'] !== 'Coordinator') {
            return;
        }

        $losesCoordinator = (string) $data['role'] !== 'Coordinator' || (string) $data['status'] !== 'Active';

        if (!$losesCoordinator) {
            return;
        }

        $count = (int) DB::value('SELECT COUNT(*) FROM users WHERE role = "Coordinator" AND status = "Active"');

        if ($count <= 1) {
            throw new ForbiddenHttpException('The last active coordinator cannot be demoted or deactivated.');
        }
    }
}
