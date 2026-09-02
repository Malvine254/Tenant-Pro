<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppRelease extends Model
{
    use HasUuids;

    protected $fillable = [
        'platform',
        'version_name',
        'version_code',
        'channel',
        'file_path',
        'file_name',
        'file_size',
        'checksum',
        'release_notes',
        'is_current',
        'is_mandatory',
        'download_count',
        'notified_at',
        'uploaded_by',
    ];

    protected $casts = [
        'version_code' => 'integer',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_current' => 'boolean',
        'is_mandatory' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeAndroid($query)
    {
        return $query->where('platform', 'ANDROID');
    }

    public static function current(string $platform = 'ANDROID'): ?self
    {
        return static::query()
            ->where('platform', $platform)
            ->where('is_current', true)
            ->latest('version_code')
            ->first();
    }

    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->file_path);
    }

    public function exists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }

    public function getSizeMbAttribute(): string
    {
        return number_format($this->file_size / 1048576, 2);
    }

    public function getLabelAttribute(): string
    {
        return 'v'.$this->version_name.' ('.$this->version_code.')';
    }

    /** Bumps the trailing numeric segment, e.g. 1.4.0 -> 1.4.1. */
    public function nextVersionName(): string
    {
        $parts = explode('.', $this->version_name);
        $last = array_key_last($parts);

        if ($last === null || ! ctype_digit($parts[$last])) {
            return $this->version_name;
        }

        $parts[$last] = (string) ((int) $parts[$last] + 1);

        return implode('.', $parts);
    }
}
