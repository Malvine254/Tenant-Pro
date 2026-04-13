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

// Public auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
	Route::get('/auth/me', [AuthController::class, 'me']);
	Route::post('/auth/logout', [AuthController::class, 'logout']);

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
	Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
});
