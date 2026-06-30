<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\View\View;

class DeploymentToolsController extends Controller
{
    public function once(Request $request): View
    {
        $token = (string) $request->query('token', '');

        return view('deployment-tools-once', [
            'isConfigured' => $this->oneTimeTokenConfigured(),
            'isValidToken' => $this->oneTimeTokenValid($token),
            'isUsed' => $this->oneTimeLinkUsed(),
            'token' => $token,
            'status' => $this->statusSnapshot(),
            'maintenanceActions' => $this->oneTimeMaintenanceActions(),
            'commandHints' => $this->commandHints(),
        ]);
    }

    public function runOnce(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'action' => 'nullable|string',
        ]);

        $token = (string) $request->input('token', '');
        $action = (string) $request->input('action', 'full_deploy');

        if (!$this->oneTimeTokenConfigured()) {
            return back()->with('error', 'DEPLOYMENT_ONE_TIME_TOKEN is not configured in .env.');
        }

        if (!$this->oneTimeTokenValid($token)) {
            return back()->with('error', 'Invalid one-time token.');
        }

        if ($action === 'full_deploy' && $this->oneTimeLinkUsed()) {
            return back()->with('error', 'This one-time deployment link has already been used.');
        }

        if ($action !== 'full_deploy' && !array_key_exists($action, $this->oneTimeMaintenanceActions())) {
            return back()->with('error', 'Unsupported one-time maintenance action.');
        }

        try {
            $output = $action === 'full_deploy'
                ? $this->runFullDeploymentSequence()
                : $this->executeAction($action);

            if ($action === 'full_deploy') {
                $this->markOneTimeLinkAsUsed();
            }

            $message = $action === 'full_deploy'
                ? 'Full deployment sequence completed. This one-time link is now locked.'
                : $this->oneTimeMaintenanceActions()[$action] . ' completed successfully.';

            return back()
                ->with('success', $message)
                ->with('command_output', $output);
        } catch (\Throwable $e) {
            return back()->with('error', 'Deployment action failed: ' . $e->getMessage());
        }
    }

    public function index(): View
    {
        $this->ensureAdmin();

        return view('admin.deployment-tools', [
            'status' => $this->statusSnapshot(),
            'availableActions' => $this->availableActions(),
            'commandHints' => $this->commandHints(),
            'toolTokenRequired' => !empty((string) env('DEPLOYMENT_TOOL_TOKEN')),
            'formAction' => route('admin.deployment-tools.run'),
            'isPublicTestingTool' => false,
        ]);
    }

    public function publicIndex(): View
    {
        return view('admin.deployment-tools', [
            'status' => $this->statusSnapshot(),
            'availableActions' => $this->publicAvailableActions(),
            'commandHints' => $this->commandHints(),
            'toolTokenRequired' => true,
            'formAction' => route('deployment-tools.testing.run'),
            'isPublicTestingTool' => true,
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $request->validate([
            'action' => 'required|string',
            'tool_token' => 'nullable|string',
        ]);

        $expectedToken = (string) env('DEPLOYMENT_TOOL_TOKEN', '');
        if ($expectedToken !== '' && !hash_equals($expectedToken, (string) $request->input('tool_token', ''))) {
            return back()->with('error', 'Invalid deployment tool token. Set DEPLOYMENT_TOOL_TOKEN in .env and use the same value here.');
        }

        $action = (string) $request->input('action');

        if (!array_key_exists($action, $this->availableActions())) {
            return back()->with('error', 'Unsupported action requested.');
        }

        try {
            $output = $this->executeAction($action);
            $message = $this->availableActions()[$action] . ' completed successfully.';

            return back()
                ->with('success', $message)
                ->with('command_output', $output);
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Action failed: ' . $e->getMessage());
        }
    }

    public function publicRun(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string',
            'tool_token' => 'required|string',
        ]);

        $expectedToken = (string) env('DEPLOYMENT_TOOL_TOKEN', '');
        if ($expectedToken === '') {
            return back()->with('error', 'DEPLOYMENT_TOOL_TOKEN is not configured in .env.');
        }

        if (!hash_equals($expectedToken, (string) $request->input('tool_token', ''))) {
            return back()->with('error', 'Invalid deployment tool token.');
        }

        $action = (string) $request->input('action');
        $availableActions = $this->publicAvailableActions();

        if (!array_key_exists($action, $availableActions)) {
            return back()->with('error', 'Unsupported action requested from testing tools.');
        }

        try {
            $output = $this->executeAction($action);
            $message = $availableActions[$action] . ' completed successfully.';

            return back()
                ->with('success', $message)
                ->with('command_output', $output);
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Action failed: ' . $e->getMessage());
        }
    }

    private function executeAction(string $action): string
    {
        return match ($action) {
            'full_deploy' => $this->runFullDeploymentSequence(),
            'clear_cache' => $this->runArtisanCommand('optimize:clear'),
            'clear_app_cache' => $this->runArtisanCommand('cache:clear'),
            'clear_config' => $this->runArtisanCommand('config:clear'),
            'clear_routes' => $this->runArtisanCommand('route:clear'),
            'clear_views' => $this->runArtisanCommand('view:clear'),
            'optimize_cache' => $this->runArtisanCommand('optimize'),
            'cache_config' => $this->runArtisanCommand('config:cache'),
            'cache_routes' => $this->runArtisanCommand('route:cache'),
            'cache_views' => $this->runArtisanCommand('view:cache'),
            'storage_link' => $this->runArtisanCommand('storage:link'),
            'migrate_status' => $this->runArtisanCommand('migrate:status'),
            'migrate_force' => $this->runArtisanCommand('migrate', ['--force' => true]),
            'migrate_rollback' => $this->runArtisanCommand('migrate:rollback', ['--force' => true]),
            'migrate_fresh_seed' => $this->runArtisanCommand('migrate:fresh', ['--seed' => true, '--force' => true]),
            'seed_force' => $this->runArtisanCommand('db:seed', ['--force' => true]),
            'generate_key' => $this->runArtisanCommand('key:generate', ['--force' => true]),
            'ensure_vendor' => $this->ensureVendorFolder(),
            default => throw new \RuntimeException('Unknown action.'),
        };
    }

    private function runArtisanCommand(string $command, array $params = []): string
    {
        Artisan::call($command, $params);

        return trim(Artisan::output()) ?: 'Command executed with no output.';
    }

    private function ensureVendorFolder(): string
    {
        $vendorPath = base_path('vendor');
        $autoloadPath = base_path('vendor/autoload.php');

        if (!File::exists($vendorPath)) {
            File::makeDirectory($vendorPath, 0755, true);
        }

        if (File::exists($autoloadPath)) {
            return 'Vendor folder and autoload.php are present.';
        }

        return 'Vendor folder exists, but vendor/autoload.php is missing. Upload vendor from local build (run composer install locally first).';
    }

    private function availableActions(): array
    {
        return [
            'full_deploy' => 'Full deployment sequence',
            'clear_cache' => 'Clear all caches',
            'clear_app_cache' => 'Clear application cache',
            'clear_config' => 'Clear config cache',
            'clear_routes' => 'Clear route cache',
            'clear_views' => 'Clear compiled views',
            'optimize_cache' => 'Optimize/cache app',
            'cache_config' => 'Rebuild config cache',
            'cache_routes' => 'Rebuild route cache',
            'cache_views' => 'Rebuild compiled views',
            'storage_link' => 'Create storage symlink',
            'migrate_status' => 'Show migration status',
            'migrate_force' => 'Run migrations (--force)',
            'migrate_rollback' => 'Rollback last migration batch',
            'migrate_fresh_seed' => 'Fresh database + seed (deletes tables)',
            'seed_force' => 'Run database seeders (--force)',
            'generate_key' => 'Generate app key',
            'ensure_vendor' => 'Ensure vendor folder exists',
        ];
    }

    private function oneTimeMaintenanceActions(): array
    {
        return [
            'clear_cache' => 'Clear all Laravel caches',
            'clear_app_cache' => 'Clear application cache',
            'clear_config' => 'Clear config cache',
            'clear_routes' => 'Clear route cache',
            'clear_views' => 'Clear compiled views',
            'cache_config' => 'Rebuild config cache',
            'cache_routes' => 'Rebuild route cache',
            'cache_views' => 'Rebuild compiled views',
            'optimize_cache' => 'Optimize/cache app',
        ];
    }

    private function publicAvailableActions(): array
    {
        return [
            'full_deploy' => 'Full deployment sequence',
            'clear_cache' => 'Clear all caches',
            'clear_app_cache' => 'Clear application cache',
            'clear_config' => 'Clear config cache',
            'clear_routes' => 'Clear route cache',
            'clear_views' => 'Clear compiled views',
            'optimize_cache' => 'Optimize/cache app',
            'cache_config' => 'Rebuild config cache',
            'cache_routes' => 'Rebuild route cache',
            'cache_views' => 'Rebuild compiled views',
            'storage_link' => 'Create storage symlink',
            'migrate_status' => 'Show migration status',
            'migrate_force' => 'Run migrations (--force)',
            'seed_force' => 'Run database seeders (--force)',
            'generate_key' => 'Generate app key',
            'ensure_vendor' => 'Ensure vendor folder exists',
        ];
    }

    private function commandHints(): array
    {
        return [
            'full_deploy' => 'storage:link, optimize:clear, migrate --force, db:seed --force, config:cache, route:cache',
            'clear_cache' => 'php artisan optimize:clear',
            'clear_app_cache' => 'php artisan cache:clear',
            'clear_config' => 'php artisan config:clear',
            'clear_routes' => 'php artisan route:clear',
            'clear_views' => 'php artisan view:clear',
            'optimize_cache' => 'php artisan optimize',
            'cache_config' => 'php artisan config:cache',
            'cache_routes' => 'php artisan route:cache',
            'cache_views' => 'php artisan view:cache',
            'storage_link' => 'php artisan storage:link',
            'migrate_status' => 'php artisan migrate:status',
            'migrate_force' => 'php artisan migrate --force',
            'migrate_rollback' => 'php artisan migrate:rollback --force',
            'migrate_fresh_seed' => 'php artisan migrate:fresh --seed --force',
            'seed_force' => 'php artisan db:seed --force',
            'generate_key' => 'php artisan key:generate --force',
            'ensure_vendor' => 'Check vendor/autoload.php',
        ];
    }

    private function statusSnapshot(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_url' => config('app.url'),
            'environment' => app()->environment(),
            'env_file_exists' => File::exists(base_path('.env')),
            'app_key_set' => !empty((string) config('app.key')),
            'vendor_autoload' => File::exists(base_path('vendor/autoload.php')),
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
        ];
    }

    private function ensureAdmin(): void
    {
        $user = auth()->user();
        if (!$user) {
            throw new HttpException(403, 'Please login first.');
        }

        try {
            $roleName = $user->role?->name;
            if ($roleName && strtoupper((string) $roleName) !== 'ADMIN') {
                throw new HttpException(403, 'Only admin users can access deployment tools.');
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // If role mapping fails on shared hosting (missing table/column),
            // keep the page accessible to authenticated users and rely on
            // DEPLOYMENT_TOOL_TOKEN for command execution protection.
        }
    }

    private function runFullDeploymentSequence(): string
    {
        $logs = [];

        $logs[] = '[1/8] ' . $this->ensureVendorFolder();

        if (empty((string) config('app.key'))) {
            $logs[] = '[2/8] ' . $this->runArtisanCommand('key:generate', ['--force' => true]);
        } else {
            $logs[] = '[2/8] APP_KEY already set. Skipped key generation.';
        }

        $logs[] = '[3/8] ' . $this->runArtisanCommand('storage:link');
        $logs[] = '[4/8] ' . $this->runArtisanCommand('optimize:clear');
        $logs[] = '[5/8] ' . $this->runArtisanCommand('migrate', ['--force' => true]);
        $logs[] = '[6/8] ' . $this->runArtisanCommand('db:seed', ['--force' => true]);
        $logs[] = '[7/8] ' . $this->runArtisanCommand('config:cache');
        $logs[] = '[8/8] ' . $this->runArtisanCommand('route:cache');

        return implode("\n\n", $logs);
    }

    private function oneTimeTokenConfigured(): bool
    {
        return !empty((string) env('DEPLOYMENT_ONE_TIME_TOKEN', ''));
    }

    private function oneTimeTokenValid(string $token): bool
    {
        $expected = (string) env('DEPLOYMENT_ONE_TIME_TOKEN', '');

        return $expected !== '' && hash_equals($expected, $token);
    }

    private function oneTimeLinkUsed(): bool
    {
        return File::exists($this->oneTimeLockPath());
    }

    private function markOneTimeLinkAsUsed(): void
    {
        File::ensureDirectoryExists(dirname($this->oneTimeLockPath()));
        File::put($this->oneTimeLockPath(), now()->toDateTimeString());
    }

    private function oneTimeLockPath(): string
    {
        return storage_path('app/deployment-tools-once-used.lock');
    }
}
