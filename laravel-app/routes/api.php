<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SupportConversationController;
use App\Http\Controllers\SupportMessageController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', fn () => response()->json([
	'status' => 'ok',
	'service' => 'tenantpro-api',
]));

Route::get('/health', fn () => response()->json([
	'status' => 'ok',
	'service' => 'tenantpro-api',
	'mobile_api_key_configured' => (string) config('deployment.mobile_api_key', '') !== '',
	'timestamp' => now()->toISOString(),
]));

// Safaricom cannot provide the mobile API key or a user access token.
Route::post('/payments/mpesa/callback', [PaymentController::class, 'mpesaCallback']);

Route::middleware('mobile.api.key')->group(function () {
	// Public auth routes
	Route::post('/auth/register', [AuthController::class, 'register']);
	Route::post('/auth/login', [AuthController::class, 'login']);
	Route::post('/auth/otp/request', [AuthController::class, 'requestPhoneOtp']);
	Route::post('/auth/otp/verify', [AuthController::class, 'verifyPhoneOtp']);
	Route::post('/auth/email-otp/request', [AuthController::class, 'requestEmailOtp']);
	Route::post('/auth/email-otp/verify', [AuthController::class, 'verifyEmailOtp']);
	Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
	Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

	// Protected routes
		Route::middleware(['auth:sanctum', 'platform.access'])->group(function () {
		Route::get('/auth/me', [AuthController::class, 'me']);
		Route::get('/users/me/profile', [AuthController::class, 'profile']);
		Route::patch('/users/me/profile', [AuthController::class, 'updateProfile']);
		Route::post('/users/me/profile-image', [AuthController::class, 'uploadProfileImage']);
		Route::post('/users/device-token', [AuthController::class, 'saveDeviceToken']);
		Route::post('/auth/logout', [AuthController::class, 'logout']);
		Route::post('/invitations/accept', [InvitationController::class, 'accept']);
		Route::post('/payments/pay', [PaymentController::class, 'pay']);
		Route::get('/payments/invoice/{invoice}', [PaymentController::class, 'forInvoice']);
		Route::post('/support/upload', [SupportMessageController::class, 'upload']);
		Route::post('/support/heartbeat', [SupportMessageController::class, 'heartbeat']);
		Route::post('/support/typing', [SupportMessageController::class, 'typing']);

		Route::apiResource('properties', PropertyController::class);
		Route::apiResource('units', UnitController::class);
		Route::apiResource('tenants', TenantController::class);
		Route::apiResource('invoices', InvoiceController::class);
		Route::apiResource('payments', PaymentController::class)->except(['update']);
		Route::apiResource('maintenance', MaintenanceRequestController::class)
			->parameters(['maintenance' => 'maintenanceRequest']);
		Route::apiResource('invitations', InvitationController::class);
		Route::apiResource('support/conversations', SupportConversationController::class)
			->parameters(['conversations' => 'supportConversation']);
		Route::apiResource('support/messages', SupportMessageController::class)
			->parameters(['messages' => 'supportMessage']);

		Route::get('/notifications', [NotificationController::class, 'index']);
		Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
		Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
		Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
		Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
	});
});
