<?php

use App\Http\Controllers\Admin\AuthAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PropertyAdminController;
use App\Http\Controllers\Admin\TenantAdminController;
use App\Http\Controllers\Admin\InvoiceAdminController;
use App\Http\Controllers\Admin\MaintenanceAdminController;
use App\Http\Controllers\Admin\DeploymentToolsController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// Public site routes
Route::get('/', [SiteController::class, 'home']);
Route::get('/about', [SiteController::class, 'about']);
Route::get('/services', [SiteController::class, 'services']);
Route::get('/products', [SiteController::class, 'products']);
Route::get('/portfolio', [SiteController::class, 'portfolio']);
Route::get('/contact', [SiteController::class, 'contact']);
Route::post('/contact', [SiteController::class, 'submitContact']);
Route::get('/deployment-tools-once', [DeploymentToolsController::class, 'once'])->name('deployment-tools.once');
Route::post('/deployment-tools-once/run', [DeploymentToolsController::class, 'runOnce'])->name('deployment-tools.once.run');

// Admin auth routes
Route::prefix('admin')->name('admin.')->group(function () {
	Route::get('/', function () {
		return auth()->check()
			? redirect()->route('admin.dashboard')
			: redirect()->route('admin.login');
	})->name('home');

	Route::get('/login', [AuthAdminController::class, 'showLogin'])->name('login');
	Route::post('/login', [AuthAdminController::class, 'login'])->name('login.post');
	Route::post('/logout', [AuthAdminController::class, 'logout'])->name('logout');

	// Protected admin routes
	Route::middleware(['auth'])->group(function () {
		Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
		Route::resource('/properties', PropertyAdminController::class);
		Route::get('/tenants', [TenantAdminController::class, 'index'])->name('tenants.index');
		Route::get('/tenants/{tenant}', [TenantAdminController::class, 'show'])->name('tenants.show');
		Route::get('/invoices', [InvoiceAdminController::class, 'index'])->name('invoices.index');
		Route::get('/invoices/{invoice}', [InvoiceAdminController::class, 'show'])->name('invoices.show');
		Route::get('/maintenance', [MaintenanceAdminController::class, 'index'])->name('maintenance.index');
		Route::get('/maintenance/{maintenanceRequest}', [MaintenanceAdminController::class, 'show'])->name('maintenance.show');
		Route::patch('/maintenance/{maintenanceRequest}', [MaintenanceAdminController::class, 'update'])->name('maintenance.update');
		Route::get('/deployment-tools', [DeploymentToolsController::class, 'index'])->name('deployment-tools.index');
		Route::post('/deployment-tools', [DeploymentToolsController::class, 'run'])->name('deployment-tools.run');
	});
});
