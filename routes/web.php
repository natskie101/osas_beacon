<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocumentRequestController as AdminDocumentRequestController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\LostFoundController as AdminLostFoundController;
use App\Http\Controllers\Admin\OrientationController as AdminOrientationController;
use App\Http\Controllers\Admin\OrientationQuizController as AdminOrientationQuizController;
use App\Http\Controllers\Admin\RegulationController as AdminRegulationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Student\AnnouncementController as StudentAnnouncementController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\DocumentRequestController as StudentDocumentRequestController;
use App\Http\Controllers\Student\EventController as StudentEventController;
use App\Http\Controllers\Student\LostFoundController as StudentLostFoundController;
use App\Http\Controllers\Student\OrientationController as StudentOrientationController;
use App\Http\Controllers\Student\OrientationQuizController as StudentOrientationQuizController;
use App\Http\Controllers\Student\RegulationController as StudentRegulationController;
use App\Http\Controllers\Student\SupportController as StudentSupportController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\VerifyCsrfToken;
use App\Support\Router;

$router = new Router([VerifyCsrfToken::class]);

/* Public authentication */
$router->get('/', [HomeController::class, 'index']);
$router->get('/login', [LoginController::class, 'show'], [RedirectIfAuthenticated::class]);
$router->post('/login', [LoginController::class, 'store'], [RedirectIfAuthenticated::class]);
$router->post('/logout', [LogoutController::class, 'destroy'], [Authenticate::class]);
$router->get('/create-account', [RegisterController::class, 'show'], [RedirectIfAuthenticated::class]);
$router->post('/create-account', [RegisterController::class, 'store'], [RedirectIfAuthenticated::class]);
$router->get('/register', [RegisterController::class, 'show'], [RedirectIfAuthenticated::class]);
$router->post('/register', [RegisterController::class, 'store'], [RedirectIfAuthenticated::class]);

$router->get('/forgot-password', [ForgotPasswordController::class, 'show']);
$router->post('/forgot-password', [ForgotPasswordController::class, 'store']);
$router->get('/reset-password/{token}', [ResetPasswordController::class, 'show']);
$router->post('/reset-password', [ResetPasswordController::class, 'store']);

/* Admin portal */
$router->get('/admin', [AdminDashboardController::class, 'index'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/admin/dashboard', [AdminDashboardController::class, 'index'], [Authenticate::class, 'role:Coordinator,Administrator']);

/* User management */
$router->get('/admin/users', [UserController::class, 'index'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/admin/users/create', [UserController::class, 'create'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/users', [UserController::class, 'store'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/admin/users/{publicId}/edit', [UserController::class, 'edit'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->put('/admin/users/{publicId}', [UserController::class, 'update'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/users/{publicId}/verify', [UserController::class, 'verify'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/users/{publicId}/reject', [UserController::class, 'reject'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/users/{publicId}/activate', [UserController::class, 'activate'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/users/{publicId}/deactivate', [UserController::class, 'deactivate'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/users/{publicId}/reset-password', [UserController::class, 'resetPassword'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/users/{publicId}/school-id', [UserController::class, 'uploadSchoolId'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/admin/users/{publicId}/school-id', [UserController::class, 'downloadSchoolId'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/admin/users/{publicId}', [UserController::class, 'show'], [Authenticate::class, 'role:Coordinator,Administrator']);

/* Student portal */
$router->get('/student', [StudentDashboardController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->get('/student/dashboard', [StudentDashboardController::class, 'index'], [Authenticate::class, 'role:Student']);

/* Notifications */
$router->get('/notifications', [NotificationController::class, 'index'], [Authenticate::class]);
$router->post('/notifications/read-all', [NotificationController::class, 'readAll'], [Authenticate::class]);
$router->post('/notifications/{id}/read', [NotificationController::class, 'read'], [Authenticate::class]);

/* Announcements */
$router->get('/admin/announcements', [AdminAnnouncementController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/announcements/create', [AdminAnnouncementController::class, 'create'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/announcements', [AdminAnnouncementController::class, 'store'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/announcements/{publicId}/edit', [AdminAnnouncementController::class, 'edit'], [Authenticate::class, 'role:Coordinator']);
$router->put('/admin/announcements/{publicId}', [AdminAnnouncementController::class, 'update'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/announcements/{publicId}/publish', [AdminAnnouncementController::class, 'publish'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/announcements/{publicId}/archive', [AdminAnnouncementController::class, 'archive'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/announcements/{publicId}/attachment/{index}/remove', [AdminAnnouncementController::class, 'removeAttachment'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/announcements/{publicId}/attachment/{fileId}', [AdminAnnouncementController::class, 'downloadAttachment'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/announcements/{publicId}', [AdminAnnouncementController::class, 'show'], [Authenticate::class, 'role:Coordinator']);

/* Regulations */
$router->get('/admin/regulations', [AdminRegulationController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/regulations/create', [AdminRegulationController::class, 'create'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/regulations', [AdminRegulationController::class, 'store'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/regulations/{publicId}/edit', [AdminRegulationController::class, 'edit'], [Authenticate::class, 'role:Coordinator']);
$router->put('/admin/regulations/{publicId}', [AdminRegulationController::class, 'update'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/regulations/{publicId}/publish', [AdminRegulationController::class, 'publish'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/regulations/{publicId}/archive', [AdminRegulationController::class, 'archive'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/regulations/{publicId}', [AdminRegulationController::class, 'show'], [Authenticate::class, 'role:Coordinator']);

/* Orientation */
$router->get('/admin/orientation', [AdminOrientationController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/orientation/create', [AdminOrientationController::class, 'create'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/orientation', [AdminOrientationController::class, 'store'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/orientation/{publicId}/edit', [AdminOrientationController::class, 'edit'], [Authenticate::class, 'role:Coordinator']);
$router->put('/admin/orientation/{publicId}', [AdminOrientationController::class, 'update'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/orientation/{publicId}/publish', [AdminOrientationController::class, 'publish'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/orientation/{publicId}/archive', [AdminOrientationController::class, 'archive'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/orientation/{publicId}/media', [AdminOrientationController::class, 'downloadMedia'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/orientation/{publicId}/quiz', [AdminOrientationQuizController::class, 'show'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/orientation/{publicId}/quiz', [AdminOrientationQuizController::class, 'saveSettings'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/orientation/{publicId}/quiz/questions', [AdminOrientationQuizController::class, 'storeQuestion'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/orientation/{publicId}/quiz/questions/{questionId}/update', [AdminOrientationQuizController::class, 'updateQuestion'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/orientation/{publicId}/quiz/questions/{questionId}/delete', [AdminOrientationQuizController::class, 'deleteQuestion'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/orientation/{publicId}', [AdminOrientationController::class, 'show'], [Authenticate::class, 'role:Coordinator']);

/* Events */
$router->get('/admin/events', [AdminEventController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/events/create', [AdminEventController::class, 'create'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/events', [AdminEventController::class, 'store'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/events/{publicId}/edit', [AdminEventController::class, 'edit'], [Authenticate::class, 'role:Coordinator']);
$router->put('/admin/events/{publicId}', [AdminEventController::class, 'update'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/events/{publicId}/cancel', [AdminEventController::class, 'cancel'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/events/{publicId}', [AdminEventController::class, 'show'], [Authenticate::class, 'role:Coordinator']);

/* Lost & Found */
$router->get('/admin/lost-found', [AdminLostFoundController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/lost-found/export', [AdminLostFoundController::class, 'export'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/lost-found/create', [AdminLostFoundController::class, 'create'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/lost-found', [AdminLostFoundController::class, 'store'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/lost-found/{publicId}/edit', [AdminLostFoundController::class, 'edit'], [Authenticate::class, 'role:Coordinator']);
$router->put('/admin/lost-found/{publicId}', [AdminLostFoundController::class, 'update'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/lost-found/{publicId}/claims/{claimId}/approve', [AdminLostFoundController::class, 'approveClaim'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/lost-found/{publicId}/claims/{claimId}/reject', [AdminLostFoundController::class, 'rejectClaim'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/lost-found/{publicId}/returned', [AdminLostFoundController::class, 'markReturned'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/lost-found/{publicId}/archive', [AdminLostFoundController::class, 'archive'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/lost-found/{publicId}/image', [AdminLostFoundController::class, 'downloadImage'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/lost-found/{publicId}', [AdminLostFoundController::class, 'show'], [Authenticate::class, 'role:Coordinator']);

/* Document requests */
$router->get('/admin/document-requests', [AdminDocumentRequestController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/document-requests/{publicId}/status', [AdminDocumentRequestController::class, 'updateStatus'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/document-requests/{publicId}', [AdminDocumentRequestController::class, 'show'], [Authenticate::class, 'role:Coordinator']);

/* Support */
$router->get('/admin/support', [AdminSupportController::class, 'index'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/support/{publicId}/reply', [AdminSupportController::class, 'reply'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->post('/admin/support/{publicId}/status', [AdminSupportController::class, 'updateStatus'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/admin/support/{publicId}', [AdminSupportController::class, 'show'], [Authenticate::class, 'role:Coordinator,Administrator']);

/* Analytics, reports, activity and broadcasts */
$router->get('/admin/analytics', [AnalyticsController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/analytics/{publicId}', [AnalyticsController::class, 'showStudent'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/reports', [ReportController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/reports/orientation/{publicId}/csv', [ReportController::class, 'moduleCsv'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/reports/orientation/{publicId}/excel', [ReportController::class, 'moduleExcel'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/reports/orientation/{publicId}', [ReportController::class, 'moduleReport'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/activity', [ActivityController::class, 'index'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/admin/broadcasts', [BroadcastController::class, 'index'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/broadcasts/create', [BroadcastController::class, 'create'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/broadcasts', [BroadcastController::class, 'store'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/broadcasts/{publicId}/edit', [BroadcastController::class, 'edit'], [Authenticate::class, 'role:Coordinator']);
$router->put('/admin/broadcasts/{publicId}', [BroadcastController::class, 'update'], [Authenticate::class, 'role:Coordinator']);
$router->post('/admin/broadcasts/{publicId}/send', [BroadcastController::class, 'send'], [Authenticate::class, 'role:Coordinator']);
$router->get('/admin/broadcasts/{publicId}', [BroadcastController::class, 'show'], [Authenticate::class, 'role:Coordinator']);

/* Student module routes */
$router->get('/student/announcements', [StudentAnnouncementController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->get('/student/announcements/{publicId}/attachment/{fileId}', [StudentAnnouncementController::class, 'downloadAttachment'], [Authenticate::class, 'role:Student']);
$router->get('/student/announcements/{publicId}', [StudentAnnouncementController::class, 'show'], [Authenticate::class, 'role:Student']);

$router->get('/student/rules', [StudentRegulationController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->post('/student/rules/{publicId}/acknowledge', [StudentRegulationController::class, 'acknowledge'], [Authenticate::class, 'role:Student']);
$router->get('/student/rules/{publicId}', [StudentRegulationController::class, 'show'], [Authenticate::class, 'role:Student']);

$router->get('/student/orientation', [StudentOrientationController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->get('/student/orientation/{publicId}/media', [StudentOrientationController::class, 'downloadMedia'], [Authenticate::class, 'role:Student']);
$router->get('/student/orientation/{publicId}/quiz', [StudentOrientationQuizController::class, 'show'], [Authenticate::class, 'role:Student']);
$router->post('/student/orientation/{publicId}/quiz/start', [StudentOrientationQuizController::class, 'start'], [Authenticate::class, 'role:Student']);
$router->post('/student/orientation/{publicId}/quiz/submit', [StudentOrientationQuizController::class, 'submit'], [Authenticate::class, 'role:Student']);
$router->get('/student/orientation/{publicId}/quiz/result', [StudentOrientationQuizController::class, 'result'], [Authenticate::class, 'role:Student']);
$router->get('/student/orientation/{publicId}', [StudentOrientationController::class, 'show'], [Authenticate::class, 'role:Student']);
$router->post('/student/orientation/{publicId}/complete', [StudentOrientationController::class, 'complete'], [Authenticate::class, 'role:Student']);

$router->get('/student/events', [StudentEventController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->get('/student/events/{publicId}', [StudentEventController::class, 'show'], [Authenticate::class, 'role:Student']);

$router->get('/student/lost-found', [StudentLostFoundController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->get('/student/lost-found/report', [StudentLostFoundController::class, 'report'], [Authenticate::class, 'role:Student']);
$router->post('/student/lost-found/report', [StudentLostFoundController::class, 'storeReport'], [Authenticate::class, 'role:Student']);
$router->get('/student/lost-found/{publicId}/image', [StudentLostFoundController::class, 'downloadImage'], [Authenticate::class, 'role:Student']);
$router->get('/student/lost-found/{publicId}', [StudentLostFoundController::class, 'show'], [Authenticate::class, 'role:Student']);
$router->post('/student/lost-found/{publicId}/claim', [StudentLostFoundController::class, 'claim'], [Authenticate::class, 'role:Student']);

$router->get('/student/document-requests', [StudentDocumentRequestController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->get('/student/document-requests/create', [StudentDocumentRequestController::class, 'create'], [Authenticate::class, 'role:Student']);
$router->post('/student/document-requests', [StudentDocumentRequestController::class, 'store'], [Authenticate::class, 'role:Student']);
$router->get('/student/document-requests/{publicId}', [StudentDocumentRequestController::class, 'show'], [Authenticate::class, 'role:Student']);
$router->post('/student/document-requests/{publicId}/cancel', [StudentDocumentRequestController::class, 'cancel'], [Authenticate::class, 'role:Student']);

$router->get('/student/support', [StudentSupportController::class, 'index'], [Authenticate::class, 'role:Student']);
$router->get('/student/support/create', [StudentSupportController::class, 'create'], [Authenticate::class, 'role:Student']);
$router->post('/student/support', [StudentSupportController::class, 'store'], [Authenticate::class, 'role:Student']);
$router->get('/student/support/{publicId}', [StudentSupportController::class, 'show'], [Authenticate::class, 'role:Student']);
$router->post('/student/support/{publicId}/reply', [StudentSupportController::class, 'reply'], [Authenticate::class, 'role:Student']);
$router->post('/student/support/{publicId}/close', [StudentSupportController::class, 'close'], [Authenticate::class, 'role:Student']);
$router->post('/student/support/{publicId}/rate', [StudentSupportController::class, 'rate'], [Authenticate::class, 'role:Student']);

/* Legacy static-site URLs. */
$router->get('/index.html', [LegacyRedirectController::class, 'index']);
$router->get('/coordinator/{page}', [LegacyRedirectController::class, 'coordinator'], [Authenticate::class, 'role:Coordinator,Administrator']);
$router->get('/student/{page}', [LegacyRedirectController::class, 'student'], [Authenticate::class, 'role:Student']);

return $router;
