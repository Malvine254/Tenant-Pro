<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;

class Property extends Model
{
    use HasUuids;

    protected $fillable = [
        'landlord_id', 'name', 'description', 'cover_image_url',
        'address_line', 'city', 'state', 'country',
        'billing_settings', 'is_publicly_listed', 'published_at',
    ];

    protected $casts = [
        'billing_settings' => 'array',
        'is_publicly_listed' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function landlord() { return $this->belongsTo(User::class, 'landlord_id'); }
    public function units() { return $this->hasMany(Unit::class); }
    public function invitations() { return $this->hasMany(Invitation::class); }
    public function marketplaceEnquiries() { return $this->hasMany(PropertyEnquiry::class); }

    /** Only properties the owning landlord has opted to list, with vacancies, and active subscription/trial access. */
    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_publicly_listed', true)
            ->whereHas('units', fn (Builder $units) => $units->where('status', 'AVAILABLE'))
            ->whereHas('landlord', fn (Builder $landlords) => $landlords
                ->where('is_active', true)
                ->withActiveSubscriptionAccess());
    }
}
