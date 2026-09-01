<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MigrationRepairService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeploymentToolsController extends Controller
{
    public function once(Request $request): View
    {
        abort_unless($this->oneTimeToolsEnabled(), 404);

        $token = (string) $request->query('token', '');
        $validToken = $this->deploymentUrlTokenValid($token);

        return view('deployment-tools-once', [
            'isConfigured' => $this->deploymentUrlTokenConfigured(),
            'isValidToken' => $validToken,
            'tokenSource' => $this->deploymentUrlTokenSource(),
            'token' => $token,
            'status' => $validToken ? $this->statusSnapshot() : [],
            'maintenanceActions' => $validToken ? $this->oneTimeMaintenanceActions() : [],
            'commandHints' => $validToken ? $this->commandHints() : [],
        ]);
    }

    public function runOnce(Request $request): RedirectResponse
    {
        abort_unless($this->oneTimeToolsEnabled(), 404);

        $request->validate([
            'token' => 'required|string',
            'action' => 'nullable|string',
        ]);

        $token = (string) $request->input('token', '');
        $action = (string) $request->input('action', 'full_deploy');

        if (! $this->deploymentUrlTokenConfigured()) {
            return back()->with('error', 'Set DEPLOYMENT_ONE_TIME_TOKEN or DEPLOYMENT_TOOL_TOKEN in .env.');
        }

        if (! $this->deploymentUrlTokenValid($token)) {
            return back()->with('error', 'Invalid deployment token.');
        }

        if ($action !== 'full_deploy' && ! array_key_exists($action, $this->oneTimeMaintenanceActions())) {
            return back()->with('error', 'Unsupported maintenance action.');
        }

        try {
            $output = $action === 'full_deploy'
                ? $this->runFullDeploymentSequence()
                : $this->executeAction($action);

            $message = $action === 'full_deploy'
                ? 'Full deployment sequence completed successfully.'
                : $this->oneTimeMaintenanceActions()[$action].' completed successfully.';

            return back()
                ->with('success', $message)
                ->with('command_output', $output);
        } catch (\Throwable $e) {
            return back()->with('error', 'Deployment action failed: '.$e->getMessage());
        }
    }

    public function index(): View
    {
        $this->ensureAdmin();

        return view('admin.deployment-tools', [
            'status' => $this->statusSnapshot(),
            'availableActions' => $this->availableActions(),
            'commandHints' => $this->commandHints(),
            'toolTokenRequired' => $this->deploymentToolToken() !== '',
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
            'confirm_operation' => 'accepted',
        ]);

        $expectedToken = $this->deploymentToolToken();
        if ($expectedToken !== '' && ! hash_equals($expectedToken, (string) $request->input('tool_token', ''))) {
            return back()->with('error', 'Invalid deployment tool token. Set DEPLOYMENT_TOOL_TOKEN in .env and use the same value here.');
        }

        $action = (string) $request->input('action');

        if (! array_key_exists($action, $this->availableActions())) {
            return back()->with('error', 'Unsupported action requested.');
        }

        try {
            $output = $this->executeAction($action);
            $message = $this->availableActions()[$action].' completed successfully.';

            return back()
                ->with('success', $message)
                ->with('command_output', $output);
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Action failed: '.$e->getMessage());
        }
    }

    public function publicRun(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string',
            'tool_token' => 'required|string',
        ]);

        $expectedToken = $this->deploymentToolToken();
        if ($expectedToken === '') {
            return back()->with('error', 'DEPLOYMENT_TOOL_TOKEN is not configured in .env.');
        }

        if (! hash_equals($expectedToken, (string) $request->input('tool_token', ''))) {
            return back()->with('error', 'Invalid deployment tool token.');
        }

        $action = (string) $request->input('action');
        $availableActions = $this->publicAvailableActions();

        if (! array_key_exists($action, $availableActions)) {
            return back()->with('error', 'Unsupported action requested from testing tools.');
        }

        try {
            $output = $this->executeAction($action);
            $message = $availableActions[$action].' completed successfully.';

            return back()
                ->with('success', $message)
                ->with('command_output', $output);
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Action failed: '.$e->getMessage());
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
            'migrate_repair' => $this->repairMigrationLog(true),
            'migrate_repair_preview' => $this->repairMigrationLog(false),
            'migrate_force' => $this->runArtisanCommand('migrate', ['--force' => true]),
            'migrate_safe' => $this->migrateIndividually(),
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

    private function repairMigrationLog(bool $apply): string
    {
        $result = app(MigrationRepairService::class)->repair($apply);
        $verb = $apply ? 'Marked as already run' : 'Would be marked as already run';

        $lines = [];
        $lines[] = $result['marked'] === []
            ? 'No migrations needed reconciling — every pending migration still has schema changes to apply.'
            : $verb.' ('.count($result['marked']).'):'.PHP_EOL.'  - '.implode(PHP_EOL.'  - ', $result['marked']);

        if ($result['pending'] !== []) {
            $lines[] = 'Still pending, will run on next migrate ('.count($result['pending']).'):'.PHP_EOL.'  - '.implode(PHP_EOL.'  - ', $result['pending']);
        }

        if ($result['missing'] !== []) {
            $lines[] = 'Not present in the database yet: '.implode(', ', $result['missing']);
        }

        return implode(PHP_EOL.PHP_EOL, $lines);
    }

    private function migrateIndividually(): string
    {
        $result = app(MigrationRepairService::class)->migrateIndividually();

        $lines = [];
        $lines[] = sprintf(
            'Applied %d, skipped %d already-present, failed %d.',
            count($result['applied']),
            count($result['skipped']),
            count($result['failed'])
        );

        foreach (['applied' => 'Applied', 'skipped' => 'Skipped (already in database)', 'failed' => 'Failed'] as $key => $heading) {
            if ($result[$key] !== []) {
                $lines[] = $heading.':'.PHP_EOL.'  - '.implode(PHP_EOL.'  - ', $result[$key]);
            }
        }

        if ($result['failed'] !== []) {
            $lines[] = 'Failed migrations were left pending. Fix the cause, then run this action again.';
        }

        return implode(PHP_EOL.PHP_EOL, $lines);
    }

    private function ensureVendorFolder(): string
    {
        $vendorPath = base_path('vendor');
        $autoloadPath = base_path('vendor/autoload.php');

        if (! File::exists($vendorPath)) {
            File::makeDirectory($vendorPath, 0755, true);
        }

        if (File::exists($autoloadPath)) {
            return 'Vendor folder and autoload.php are present.';
        }

        return 'Vendor folder exists, but vendor/autoload.php is missing. Upload vendor from local build (run composer install locally first).';
    }

    private function availableActions(): array
    {
        $actions = [
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
            'migrate_repair_preview' => 'Preview migration log repair',
            'migrate_repair' => 'Repair migration log (skip existing tables)',
            'migrate_force' => 'Run migrations (--force)',
            'migrate_safe' => 'Run migrations one by one (skip failures)',
            'seed_force' => 'Run database seeders (--force)',
            'ensure_vendor' => 'Ensure vendor folder exists',
        ];

        if (app()->environment(['local', 'testing'])) {
            $actions['migrate_rollback'] = 'Rollback last migration batch';
            $actions['migrate_fresh_seed'] = 'Fresh database + seed (deletes tables)';
            $actions['generate_key'] = 'Generate app key';
        }

        return $actions;
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
            'migrate_status' => 'Show migration status',
            'migrate_repair_preview' => 'Preview migration log repair',
            'migrate_repair' => 'Repair migration log (skip existing tables)',
            'migrate_force' => 'Run migrations (--force)',
            'migrate_safe' => 'Run migrations one by one (skip failures)',
            'seed_force' => 'Run database seeders (--force)',
            'storage_link' => 'Create storage symlink',
            'generate_key' => 'Generate app key',
            'ensure_vendor' => 'Ensure vendor folder exists',
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
            'migrate_repair_preview' => 'Preview migration log repair',
            'migrate_repair' => 'Repair migration log (skip existing tables)',
            'migrate_force' => 'Run migrations (--force)',
            'migrate_safe' => 'Run migrations one by one (skip failures)',
            'seed_force' => 'Run database seeders (--force)',
            'generate_key' => 'Generate app key',
            'ensure_vendor' => 'Ensure vendor folder exists',
        ];
    }

    private function commandHints(): array
    {
        return [
            'full_deploy' => 'storage:link, optimize:clear, repair migration log, migrate --force, db:seed --force, config:cache, route:cache',
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
            'migrate_repair_preview' => 'Dry run: list migrations whose tables already exist',
            'migrate_repair' => 'Logs already-created tables as migrated so migrate skips them',
            'migrate_force' => 'php artisan migrate --force',
            'migrate_safe' => 'Runs each pending migration separately and continues past errors',
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
            'app_key_set' => ! empty((string) config('app.key')),
            'vendor_autoload' => File::exists(base_path('vendor/autoload.php')),
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
        ];
    }

    private function ensureAdmin(): void
    {
        $user = auth()->user();
        if (! $user) {
            throw new HttpException(403, 'Please login first.');
        }

        try {
            $roleName = $user->role?->name;
            if (strtoupper((string) $roleName) !== 'SUPER_ADMIN') {
                throw new HttpException(403, 'Only super administrators can access deployment tools.');
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new HttpException(403, 'Unable to verify administrator access.');
        }
    }

    private function runFullDeploymentSequence(): string
    {
        $logs = [];

        $logs[] = '[1/9] '.$this->ensureVendorFolder();

        if (empty((string) config('app.key'))) {
            $logs[] = '[2/9] '.$this->runArtisanCommand('key:generate', ['--force' => true]);
        } else {
            $logs[] = '[2/9] APP_KEY already set. Skipped key generation.';
        }

        $logs[] = '[3/9] '.$this->runArtisanCommand('storage:link');
        $logs[] = '[4/9] '.$this->runArtisanCommand('optimize:clear');
        $logs[] = '[5/9] '.$this->repairMigrationLog(true);
        $logs[] = '[6/9] '.$this->migrateIndividually();
        $logs[] = '[7/9] '.$this->runArtisanCommand('db:seed', ['--force' => true]);
        $logs[] = '[8/9] '.$this->runArtisanCommand('config:cache');
        $logs[] = '[9/9] '.$this->runArtisanCommand('route:cache');

        return implode("\n\n", $logs);
    }

    private function deploymentUrlTokenConfigured(): bool
    {
        return $this->deploymentUrlToken() !== '';
    }

    /**
     * The page exists whenever a token is configured. DEPLOYMENT_ONE_TIME_ENABLED
     * is only an explicit kill switch, so a missing flag can never lock an operator
     * out of the recovery tools on hosting without shell access.
     */
    private function oneTimeToolsEnabled(): bool
    {
        if (! $this->deploymentUrlTokenConfigured()) {
            return false;
        }

        $flag = config('deployment.one_time_enabled');

        if ($flag === null || $flag === '') {
            return true;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;
    }

    private function deploymentUrlTokenValid(string $token): bool
    {
        $expected = $this->deploymentUrlToken();

        return $expected !== '' && hash_equals($expected, $token);
    }

    private function deploymentUrlToken(): string
    {
        $oneTimeToken = (string) config('deployment.deployment_one_time_token', '');
        if ($oneTimeToken !== '') {
            return $oneTimeToken;
        }

        return $this->deploymentToolToken();
    }

    private function deploymentUrlTokenSource(): string
    {
        return (string) config('deployment.deployment_one_time_token', '') !== ''
            ? 'DEPLOYMENT_ONE_TIME_TOKEN'
            : 'DEPLOYMENT_TOOL_TOKEN';
    }

    private function deploymentToolToken(): string
    {
        return (string) config('deployment.deployment_tool_token', '');
    }
}
