<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class PlatformSettingsService
{
    private const MAINTENANCE_CACHE_KEY = 'platform.maintenance.status';

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return PlatformSetting::query()->find($key)?->value ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }

    public function setMany(array $settings, User $actor): void
    {
        DB::transaction(function () use ($settings, $actor): void {
            foreach ($settings as $key => $value) {
                PlatformSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $value, 'updated_by' => $actor->id],
                );
            }
        });

        Cache::forget(self::MAINTENANCE_CACHE_KEY);
    }

    public function daraja(): array
    {
        return [
            'environment' => $this->get('daraja.environment', config('services.mpesa.environment', 'sandbox')),
            'consumer_key' => $this->get('daraja.consumer_key', config('services.mpesa.consumer_key')),
            'consumer_secret' => $this->get('daraja.consumer_secret', config('services.mpesa.consumer_secret')),
            'shortcode' => $this->get('daraja.shortcode', config('services.mpesa.shortcode')),
            'passkey' => $this->get('daraja.passkey', config('services.mpesa.passkey')),
            'callback_url' => $this->get('daraja.callback_url', config('services.mpesa.callback_url')),
            'simulate' => filter_var(
                $this->get('daraja.simulate', config('services.mpesa.simulate', false)),
                FILTER_VALIDATE_BOOL,
            ),
        ];
    }

    public function maintenance(): array
    {
        return Cache::remember(self::MAINTENANCE_CACHE_KEY, now()->addSeconds(15), fn () => [
            'enabled' => filter_var($this->get('maintenance.enabled', false), FILTER_VALIDATE_BOOL),
            'message' => (string) $this->get(
                'maintenance.message',
                'TenantPro is temporarily unavailable while essential maintenance is completed. Please try again shortly.',
            ),
        ]);
    }
}
