<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\Admin\SystemOverviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\AnnouncementController as StudentAnnouncementController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\DocumentRequestController as StudentDocumentRequestController;
use App\Http\Controllers\Student\EventController as StudentEventController;
use App\Http\Controllers\Student\LostFoundController as StudentLostFoundController;
use App\Http\Controllers\Student\OrientationController as StudentOrientationController;
use App\Http\Controllers\Student\OrientationQuizController as StudentOrientationQuizController;
use App\Http\Controllers\Student\RegulationController as StudentRegulationController;
use App\Http\Controllers\Student\SupportController as StudentSupportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index']);

/* Guest routes */
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'show']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/create-account', [RegisterController::class, 'show']);
    Route::post('/create-account', [RegisterController::class, 'store'])->middleware('throttle:30,3600');
    Route::get('/register', [RegisterController::class, 'show']);
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:30,3600');
});

/* Password reset */
Route::get('/forgot-password', [ForgotPasswordController::class, 'show']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:10,3600');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show']);
Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:10,600');

/* Authenticated routes */
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy']);
    
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

    /* Admin/Coordinator routes */
    Route::middleware(['role:Coordinator,Administrator'])->group(function () {
        Route::get('/admin', [AdminDashboardController::class, 'index']);
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
        
        /* User management */
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::get('/admin/users/create', [UserController::class, 'create']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::get('/admin/users/{publicId}/edit', [UserController::class, 'edit']);
        Route::put('/admin/users/{publicId}', [UserController::class, 'update']);
        Route::post('/admin/users/{publicId}/verify', [UserController::class, 'verify']);
        Route::post('/admin/users/{publicId}/reject', [UserController::class, 'reject']);
        Route::post('/admin/users/{publicId}/activate', [UserController::class, 'activate']);
        Route::post('/admin/users/{publicId}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/admin/users/{publicId}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/admin/users/{publicId}/school-id', [UserController::class, 'uploadSchoolId']);
        Route::get('/admin/users/{publicId}/school-id', [UserController::class, 'downloadSchoolId']);
        Route::get('/admin/users/{publicId}', [UserController::class, 'show']);
        
        /* Support */
        Route::get('/admin/support', [AdminSupportController::class, 'index']);
        Route::post('/admin/support/{publicId}/reply', [AdminSupportController::class, 'reply']);
        Route::post('/admin/support/{publicId}/status', [AdminSupportController::class, 'updateStatus']);
        Route::get('/admin/support/{publicId}/attachment/{fileId}', [StudentSupportController::class, 'downloadImage']);
        Route::get('/admin/support/{publicId}', [AdminSupportController::class, 'show']);

        Route::get('/admin/activity', [ActivityController::class, 'index']);
    });

    /* Administrator only */
    Route::middleware(['role:Administrator'])->group(function () {
        Route::get('/admin/system-overview', [SystemOverviewController::class, 'index']);
        Route::post('/admin/users/{publicId}/archive', [UserController::class, 'archive']);
        Route::post('/admin/users/{publicId}/restore', [UserController::class, 'restore']);
    });

    /* Coordinator only */
    Route::middleware(['role:Coordinator'])->group(function () {
        Route::get('/admin/announcements', [AdminAnnouncementController::class, 'index']);
        Route::get('/admin/announcements/create', [AdminAnnouncementController::class, 'create']);
        Route::post('/admin/announcements', [AdminAnnouncementController::class, 'store']);
        Route::get('/admin/announcements/{publicId}/edit', [AdminAnnouncementController::class, 'edit']);
        Route::put('/admin/announcements/{publicId}', [AdminAnnouncementController::class, 'update']);
        Route::post('/admin/announcements/{publicId}/publish', [AdminAnnouncementController::class, 'publish']);
        Route::post('/admin/announcements/{publicId}/archive', [AdminAnnouncementController::class, 'archive']);
        Route::post('/admin/announcements/{publicId}/attachment/{index}/remove', [AdminAnnouncementController::class, 'removeAttachment']);
        Route::get('/admin/announcements/{publicId}/attachment/{fileId}', [AdminAnnouncementController::class, 'downloadAttachment']);
        Route::get('/admin/announcements/{publicId}', [AdminAnnouncementController::class, 'show']);

        Route::get('/admin/regulations', [AdminRegulationController::class, 'index']);
        Route::get('/admin/regulations/create', [AdminRegulationController::class, 'create']);
        Route::post('/admin/regulations', [AdminRegulationController::class, 'store']);
        Route::get('/admin/regulations/{publicId}/edit', [AdminRegulationController::class, 'edit']);
        Route::put('/admin/regulations/{publicId}', [AdminRegulationController::class, 'update']);
        Route::post('/admin/regulations/{publicId}/publish', [AdminRegulationController::class, 'publish']);
        Route::post('/admin/regulations/{publicId}/archive', [AdminRegulationController::class, 'archive']);
        Route::get('/admin/regulations/{publicId}', [AdminRegulationController::class, 'show']);

        Route::get('/admin/orientation', [AdminOrientationController::class, 'index']);
        Route::get('/admin/orientation/create', [AdminOrientationController::class, 'create']);
        Route::post('/admin/orientation', [AdminOrientationController::class, 'store']);
        Route::get('/admin/orientation/{publicId}/edit', [AdminOrientationController::class, 'edit']);
        Route::put('/admin/orientation/{publicId}', [AdminOrientationController::class, 'update']);
        Route::post('/admin/orientation/{publicId}/publish', [AdminOrientationController::class, 'publish']);
        Route::post('/admin/orientation/{publicId}/archive', [AdminOrientationController::class, 'archive']);
        Route::get('/admin/orientation/{publicId}/media', [AdminOrientationController::class, 'downloadMedia']);
        Route::get('/admin/orientation/{publicId}/quiz', [AdminOrientationQuizController::class, 'show']);
        Route::post('/admin/orientation/{publicId}/quiz', [AdminOrientationQuizController::class, 'saveSettings']);
        Route::post('/admin/orientation/{publicId}/quiz/questions', [AdminOrientationQuizController::class, 'storeQuestion']);
        Route::post('/admin/orientation/{publicId}/quiz/questions/{questionId}/update', [AdminOrientationQuizController::class, 'updateQuestion']);
        Route::post('/admin/orientation/{publicId}/quiz/questions/{questionId}/delete', [AdminOrientationQuizController::class, 'deleteQuestion']);
        Route::get('/admin/orientation/{publicId}', [AdminOrientationController::class, 'show']);

        Route::get('/admin/events', [AdminEventController::class, 'index']);
        Route::get('/admin/events/create', [AdminEventController::class, 'create']);
        Route::post('/admin/events', [AdminEventController::class, 'store']);
        Route::get('/admin/events/{publicId}/edit', [AdminEventController::class, 'edit']);
        Route::get('/admin/events/{publicId}/attachment/{fileId}', [AdminEventController::class, 'downloadAttachment']);
        Route::put('/admin/events/{publicId}', [AdminEventController::class, 'update']);
        Route::post('/admin/events/{publicId}/cancel', [AdminEventController::class, 'cancel']);
        Route::get('/admin/events/{publicId}', [AdminEventController::class, 'show']);

        Route::get('/admin/lost-found', [AdminLostFoundController::class, 'index']);
        Route::get('/admin/lost-found/export', [AdminLostFoundController::class, 'export']);
        Route::get('/admin/lost-found/create', [AdminLostFoundController::class, 'create']);
        Route::post('/admin/lost-found', [AdminLostFoundController::class, 'store']);
        Route::get('/admin/lost-found/{publicId}/edit', [AdminLostFoundController::class, 'edit']);
        Route::put('/admin/lost-found/{publicId}', [AdminLostFoundController::class, 'update']);
        Route::post('/admin/lost-found/{publicId}/claims/{claimId}/approve', [AdminLostFoundController::class, 'approveClaim']);
        Route::post('/admin/lost-found/{publicId}/claims/{claimId}/reject', [AdminLostFoundController::class, 'rejectClaim']);
        Route::post('/admin/lost-found/{publicId}/returned', [AdminLostFoundController::class, 'markReturned']);
        Route::post('/admin/lost-found/{publicId}/archive', [AdminLostFoundController::class, 'archive']);
        Route::get('/admin/lost-found/{publicId}/image', [AdminLostFoundController::class, 'downloadImage']);
        Route::get('/admin/lost-found/{publicId}', [AdminLostFoundController::class, 'show']);

        Route::get('/admin/document-requests', [AdminDocumentRequestController::class, 'index']);
        Route::post('/admin/document-requests/{publicId}/status', [AdminDocumentRequestController::class, 'updateStatus']);
        Route::get('/admin/document-requests/{publicId}', [AdminDocumentRequestController::class, 'show']);

        Route::get('/admin/analytics', [AnalyticsController::class, 'index']);
        Route::post('/admin/analytics/notify', [AnalyticsController::class, 'notifyAllNonCompliant']);
        Route::post('/admin/analytics/{publicId}/notify', [AnalyticsController::class, 'notifyStudent']);
        Route::get('/admin/analytics/{publicId}', [AnalyticsController::class, 'showStudent']);
        
        Route::get('/admin/reports', [ReportController::class, 'index']);
        Route::get('/admin/reports/orientation/{publicId}/csv', [ReportController::class, 'moduleCsv']);
        Route::get('/admin/reports/orientation/{publicId}/excel', [ReportController::class, 'moduleExcel']);
        Route::get('/admin/reports/orientation/{publicId}', [ReportController::class, 'moduleReport']);
        
        Route::get('/admin/broadcasts', [BroadcastController::class, 'index']);
        Route::get('/admin/broadcasts/create', [BroadcastController::class, 'create']);
        Route::post('/admin/broadcasts', [BroadcastController::class, 'store']);
        Route::get('/admin/broadcasts/{publicId}/edit', [BroadcastController::class, 'edit']);
        Route::put('/admin/broadcasts/{publicId}', [BroadcastController::class, 'update']);
        Route::post('/admin/broadcasts/{publicId}/send', [BroadcastController::class, 'send']);
        Route::get('/admin/broadcasts/{publicId}', [BroadcastController::class, 'show']);
    });

    /* Shared routes */
    Route::middleware(['role:Coordinator,Student'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::get('/profile/photo', [ProfileController::class, 'photo']);
        Route::put('/profile', [ProfileController::class, 'update']);
    });

    /* Student portal */
    Route::middleware(['role:Student'])->group(function () {
        Route::get('/student', [StudentDashboardController::class, 'index']);
        Route::get('/student/dashboard', [StudentDashboardController::class, 'index']);

        Route::get('/student/announcements', [StudentAnnouncementController::class, 'index']);
        Route::get('/student/announcements/{publicId}/attachment/{fileId}', [StudentAnnouncementController::class, 'downloadAttachment']);
        Route::get('/student/announcements/{publicId}', [StudentAnnouncementController::class, 'show']);

        Route::get('/student/rules', [StudentRegulationController::class, 'index']);
        Route::post('/student/rules/{publicId}/acknowledge', [StudentRegulationController::class, 'acknowledge']);
        Route::get('/student/rules/{publicId}', [StudentRegulationController::class, 'show']);

        Route::get('/student/orientation', [StudentOrientationController::class, 'index']);
        Route::get('/student/orientation/{publicId}/media', [StudentOrientationController::class, 'downloadMedia']);
        Route::get('/student/orientation/{publicId}/quiz', [StudentOrientationQuizController::class, 'show']);
        Route::post('/student/orientation/{publicId}/quiz/start', [StudentOrientationQuizController::class, 'start']);
        Route::post('/student/orientation/{publicId}/quiz/submit', [StudentOrientationQuizController::class, 'submit']);
        Route::get('/student/orientation/{publicId}/quiz/result', [StudentOrientationQuizController::class, 'result']);
        Route::get('/student/orientation/{publicId}', [StudentOrientationController::class, 'show']);
        Route::post('/student/orientation/{publicId}/complete', [StudentOrientationController::class, 'complete']);

        Route::get('/student/events', [StudentEventController::class, 'index']);
        Route::get('/student/events/{publicId}/attachment/{fileId}', [StudentEventController::class, 'downloadAttachment']);
        Route::get('/student/events/{publicId}', [StudentEventController::class, 'show']);

        Route::get('/student/lost-found', [StudentLostFoundController::class, 'index']);
        Route::get('/student/lost-found/report', [StudentLostFoundController::class, 'report']);
        Route::post('/student/lost-found/report', [StudentLostFoundController::class, 'storeReport']);
        Route::get('/student/lost-found/{publicId}/image', [StudentLostFoundController::class, 'downloadImage']);
        Route::get('/student/lost-found/{publicId}', [StudentLostFoundController::class, 'show']);
        Route::post('/student/lost-found/{publicId}/claim', [StudentLostFoundController::class, 'claim']);

        Route::get('/student/document-requests', [StudentDocumentRequestController::class, 'index']);
        Route::get('/student/document-requests/create', [StudentDocumentRequestController::class, 'create']);
        Route::post('/student/document-requests', [StudentDocumentRequestController::class, 'store']);
        Route::get('/student/document-requests/{publicId}', [StudentDocumentRequestController::class, 'show']);
        Route::post('/student/document-requests/{publicId}/cancel', [StudentDocumentRequestController::class, 'cancel']);

        Route::get('/student/support', [StudentSupportController::class, 'index']);
        Route::get('/student/support/create', [StudentSupportController::class, 'create']);
        Route::post('/student/support', [StudentSupportController::class, 'store']);
        Route::get('/student/support/{publicId}/attachment/{fileId}', [StudentSupportController::class, 'downloadImage']);
        Route::get('/student/support/{publicId}', [StudentSupportController::class, 'show']);
        Route::post('/student/support/{publicId}/reply', [StudentSupportController::class, 'reply']);
        Route::post('/student/support/{publicId}/close', [StudentSupportController::class, 'close']);
        Route::post('/student/support/{publicId}/rate', [StudentSupportController::class, 'rate']);
        
        Route::get('/student/{page}', [LegacyRedirectController::class, 'student']);
    });

    /* AI Services (NVIDIA API) */
    Route::middleware(['throttle:20,60'])->group(function () {
        Route::post('/ai/chat', [AiChatController::class, 'ask']);
        Route::get('/ai/stream', [AiChatController::class, 'stream']);
    });

    Route::get('/coordinator/{page}', [LegacyRedirectController::class, 'coordinator'])->middleware('role:Coordinator,Administrator');
});

/* Legacy static-site URLs */
Route::get('/index.html', [LegacyRedirectController::class, 'index']);
