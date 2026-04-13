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
    public function index(): View
    {
        $this->ensureAdmin();

        return view('admin.deployment-tools', [
            'status' => $this->statusSnapshot(),
            'availableActions' => $this->availableActions(),
            'toolTokenRequired' => !empty((string) env('DEPLOYMENT_TOOL_TOKEN')),
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

    private function executeAction(string $action): string
    {
        return match ($action) {
            'clear_cache' => $this->runArtisanCommand('optimize:clear'),
            'cache_config' => $this->runArtisanCommand('config:cache'),
            'cache_routes' => $this->runArtisanCommand('route:cache'),
            'cache_views' => $this->runArtisanCommand('view:cache'),
            'storage_link' => $this->runArtisanCommand('storage:link'),
            'migrate_force' => $this->runArtisanCommand('migrate', ['--force' => true]),
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
            'clear_cache' => 'Clear all caches',
            'cache_config' => 'Rebuild config cache',
            'cache_routes' => 'Rebuild route cache',
            'cache_views' => 'Rebuild compiled views',
            'storage_link' => 'Create storage symlink',
            'migrate_force' => 'Run migrations (--force)',
            'seed_force' => 'Run database seeders (--force)',
            'generate_key' => 'Generate app key',
            'ensure_vendor' => 'Ensure vendor folder exists',
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
        $roleName = $user?->role?->name;

        if ($roleName !== 'ADMIN') {
            throw new HttpException(403, 'Only admin users can access deployment tools.');
        }
    }
}
