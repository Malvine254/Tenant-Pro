<?php

namespace App\Services;

use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppReleaseService
{
    private const DIRECTORY = 'app-releases';

    public function __construct(private readonly TenantAppNotificationService $notifications) {}

    /**
     * Store an uploaded build. Only the newest version code stays flagged as current.
     */
    public function publish(UploadedFile $file, array $data, ?User $actor = null): AppRelease
    {
        $versionName = trim((string) $data['version_name']);
        $versionCode = (int) $data['version_code'];
        $platform = strtoupper((string) ($data['platform'] ?? 'ANDROID'));

        $fileName = sprintf('tenantpro-%s-%d.apk', Str::slug($versionName, '.'), $versionCode);
        $path = $file->storeAs(self::DIRECTORY, $fileName, 'local');

        return DB::transaction(function () use ($path, $fileName, $file, $versionName, $versionCode, $platform, $data, $actor) {
            $makeCurrent = (bool) ($data['is_current'] ?? true);

            if ($makeCurrent) {
                AppRelease::query()->where('platform', $platform)->update(['is_current' => false]);
            }

            return AppRelease::create([
                'platform' => $platform,
                'version_name' => $versionName,
                'version_code' => $versionCode,
                'channel' => strtoupper((string) ($data['channel'] ?? 'PRODUCTION')),
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => $file->getSize() ?: Storage::disk('local')->size($path),
                'checksum' => hash_file('sha256', Storage::disk('local')->path($path)),
                'release_notes' => $data['release_notes'] ?? null,
                'is_current' => $makeCurrent,
                'is_mandatory' => (bool) ($data['is_mandatory'] ?? false),
                'uploaded_by' => $actor?->id,
            ]);
        });
    }

    public function makeCurrent(AppRelease $release): void
    {
        DB::transaction(function () use ($release) {
            AppRelease::query()->where('platform', $release->platform)->update(['is_current' => false]);
            $release->update(['is_current' => true]);
        });
    }

    public function delete(AppRelease $release): void
    {
        Storage::disk('local')->delete($release->file_path);
        $release->delete();
    }

    /**
     * Fan out an in-app + push notification about a release. Returns the recipient count.
     */
    public function announce(AppRelease $release): int
    {
        $title = 'TenantPro '.$release->label.' is available';
        $notes = trim((string) $release->release_notes);
        $body = $notes !== ''
            ? Str::limit($notes, 160)
            : 'A new version of the TenantPro app is ready to download.';

        if ($release->is_mandatory) {
            $body .= ' This update is required to keep using the app.';
        }

        $metadata = [
            'release_id' => $release->id,
            'version_name' => $release->version_name,
            'version_code' => $release->version_code,
            'is_mandatory' => $release->is_mandatory,
            'download_url' => route('downloads.apk.public'),
        ];

        $sent = 0;

        User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'TENANT'))
            ->chunkById(200, function ($users) use (&$sent, $title, $body, $metadata) {
                foreach ($users as $user) {
                    /** @var User $user */
                    try {
                        if ($this->notifications->notify($user, 'APP_UPDATE', $title, $body, $metadata)) {
                            $sent++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('App update notification failed for a user.', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $release->update(['notified_at' => now()]);

        return $sent;
    }
}
