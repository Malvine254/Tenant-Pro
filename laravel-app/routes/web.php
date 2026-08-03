<?php

use App\Http\Controllers\Admin\AuthAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PropertyAdminController;
use App\Http\Controllers\Admin\TenantAdminController;
use App\Http\Controllers\Admin\InvoiceAdminController;
use App\Http\Controllers\Admin\LandlordAdminController;
use App\Http\Controllers\Admin\SupportChatAdminController;
use App\Http\Controllers\Admin\DeploymentToolsController;
use App\Http\Controllers\Admin\PropertyUnitAdminController;
use App\Http\Controllers\Admin\InvitationAdminController;
use App\Http\Controllers\DownloadsController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/invite', function (Request $request) {
    $code = strtoupper(trim((string) $request->query('code')));
    abort_if($code === '' || ! preg_match('/^[A-Z0-9]+$/', $code), 404);

	$downloadUrl = env('TENANT_APP_DOWNLOAD_URL', rtrim(config('app.url'), '/').'/download/apk');
	$deepLink = 'tenantpro://invite?code='.urlencode($code);

	return view('site.invite-app', [
		'code' => $code,
		'downloadUrl' => $downloadUrl,
		'deepLink' => $deepLink,
	]);
})->name('tenant.invite.open');

// Public download endpoint (no auth required)
Route::get('/download/apk', function () {
    $filePath = public_path('downloads/app-debug.apk');
    if (!file_exists($filePath)) {
        abort(404, 'APK file not found');
    }
    return response()->download($filePath, 'TenantPro-App.apk');
})->name('downloads.apk.public');

// Send the app subdomain straight to the Laravel admin area.
Route::redirect('/', '/admin');
Route::redirect('/login', '/admin/login')->name('login');
Route::get('/home', [SiteController::class, 'home'])->name('site.home');
Route::get('/about', [SiteController::class, 'about']);
Route::get('/services', [SiteController::class, 'services']);
Route::get('/products', [SiteController::class, 'products']);
Route::get('/portfolio', [SiteController::class, 'portfolio']);
Route::get('/contact', [SiteController::class, 'contact']);
Route::post('/contact', [SiteController::class, 'submitContact']);
Route::get('/deployment-tools-once', [DeploymentToolsController::class, 'once'])->name('deployment-tools.once');
Route::post('/deployment-tools-once/run', [DeploymentToolsController::class, 'runOnce'])->name('deployment-tools.once.run');
Route::get('/deployment-tools-testing', [DeploymentToolsController::class, 'publicIndex'])->name('deployment-tools.testing');
Route::post('/deployment-tools-testing', [DeploymentToolsController::class, 'publicRun'])->name('deployment-tools.testing.run');

// Admin auth routes
Route::prefix('admin')->name('admin.')->group(function () {
	Route::redirect('/', '/admin/login')->name('home');

	Route::get('/login', [AuthAdminController::class, 'showLogin'])->name('login');
	Route::post('/login', [AuthAdminController::class, 'login'])->name('login.post');
	Route::post('/logout', [AuthAdminController::class, 'logout'])->name('logout');

	// Protected admin routes
	Route::middleware(['auth', 'admin.role:SUPER_ADMIN,ADMIN,LANDLORD'])->group(function () {
		Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

		Route::middleware('admin.role:SUPER_ADMIN,ADMIN')->group(function () {
			Route::patch('/landlords/{landlord}/status', [LandlordAdminController::class, 'updateStatus'])->name('landlords.status');
			Route::post('/landlords/{landlord}/payments', [LandlordAdminController::class, 'recordPayment'])->name('landlords.payments.record');
			Route::resource('/landlords', LandlordAdminController::class)->only(['index', 'create', 'store', 'edit', 'update']);
			Route::get('/deployment-tools', [DeploymentToolsController::class, 'index'])->name('deployment-tools.index');
			Route::post('/deployment-tools', [DeploymentToolsController::class, 'run'])->name('deployment-tools.run');
		});

		Route::get('/units', [PropertyUnitAdminController::class, 'index'])->name('units.index');
		Route::get('/properties/{property}/units/create', [PropertyUnitAdminController::class, 'create'])->name('properties.units.create');
		Route::post('/properties/{property}/units', [PropertyUnitAdminController::class, 'store'])->name('properties.units.store');
		Route::get('/properties/{property}/units/{unit}/edit', [PropertyUnitAdminController::class, 'edit'])->name('properties.units.edit');
		Route::put('/properties/{property}/units/{unit}', [PropertyUnitAdminController::class, 'update'])->name('properties.units.update');
		Route::delete('/properties/{property}/units/{unit}', [PropertyUnitAdminController::class, 'destroy'])->name('properties.units.destroy');
		Route::resource('/properties', PropertyAdminController::class);
		Route::get('/tenants/create', [TenantAdminController::class, 'create'])->name('tenants.create');
		Route::post('/tenants', [TenantAdminController::class, 'store'])->name('tenants.store');
		Route::get('/tenants/assign', [TenantAdminController::class, 'assign'])->name('tenants.assign');
		Route::post('/tenants/assign', [TenantAdminController::class, 'assignStore'])->name('tenants.assign.store');
		Route::get('/tenants', [TenantAdminController::class, 'index'])->name('tenants.index');
		Route::get('/tenants/{tenant}', [TenantAdminController::class, 'show'])->name('tenants.show');
		Route::patch('/tenants/{tenant}/unassign', [TenantAdminController::class, 'unassign'])->name('tenants.unassign');
		Route::get('/invitations', [InvitationAdminController::class, 'index'])->name('invitations.index');
		Route::post('/invitations/tenants', [InvitationAdminController::class, 'storeTenant'])->name('invitations.tenants.store');
		Route::post('/invitations/landlords', [InvitationAdminController::class, 'storeLandlord'])->name('invitations.landlords.store');
		Route::patch('/invitations/{invitation}/resend', [InvitationAdminController::class, 'resend'])->name('invitations.resend');
		Route::patch('/invitations/{invitation}/cancel', [InvitationAdminController::class, 'cancel'])->name('invitations.cancel');
		Route::get('/invoices', [InvoiceAdminController::class, 'index'])->name('invoices.index');
		Route::get('/invoices/{invoice}', [InvoiceAdminController::class, 'show'])->name('invoices.show');
		Route::get('/chats', [SupportChatAdminController::class, 'index'])->name('chats.index');
		Route::post('/chats/{supportConversation}/reply', [SupportChatAdminController::class, 'reply'])->name('chats.reply');
		Route::patch('/chats/{supportConversation}/toggle', [SupportChatAdminController::class, 'toggle'])->name('chats.toggle');
		Route::get('/chats/{supportConversation}/state', [SupportChatAdminController::class, 'state'])->name('chats.state');
		Route::post('/chats/{supportConversation}/typing', [SupportChatAdminController::class, 'typing'])->name('chats.typing');
		Route::get('/downloads', [DownloadsController::class, 'index'])->name('downloads.index');
		Route::get('/downloads/apk/download', function () {
			$filePath = public_path('downloads/app-debug.apk');
			if (!file_exists($filePath)) {
				abort(404, 'APK file not found');
			}
			return response()->download($filePath, 'TenantPro-App.apk');
		})->name('downloads.apk.download');
	});
});
