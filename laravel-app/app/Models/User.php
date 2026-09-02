<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name', 'email', 'password', 'first_name', 'last_name',
        'phone_number', 'profile_image_url', 'emergency_contact_name',
        'emergency_contact_phone', 'bio', 'is_active', 'role_id',
        'app_settings',
        'fcm_token', 'requires_subscription', 'billing_status',
        'trial_started_at', 'trial_ends_at', 'service_paid_until',
        'subscription_started_at', 'subscription_last_paid_at',
        'monthly_service_fee',
        'requires_password_change',
        'managed_landlord_id', 'team_invited_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'requires_password_change' => 'boolean',
            'app_settings' => 'array',
            'requires_subscription' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'service_paid_until' => 'datetime',
            'subscription_started_at' => 'datetime',
            'subscription_last_paid_at' => 'datetime',
            'monthly_service_fee' => 'decimal:2',
            'team_invited_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'landlord_id');
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function tenancies()
    {
        return $this->hasMany(Tenant::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'tenant_id');
    }

    public function appNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function supportConversations()
    {
        return $this->hasMany(SupportConversation::class, 'tenant_user_id');
    }

    public function landlordAccountOwner()
    {
        return $this->belongsTo(User::class, 'managed_landlord_id');
    }

    public function landlordTeamMembers()
    {
        return $this->hasMany(User::class, 'managed_landlord_id');
    }

    public function receivedInvitations()
    {
        return $this->hasMany(Invitation::class, 'email', 'email');
    }

    public function isLandlord(): bool
    {
        return $this->role?->name === 'LANDLORD';
    }

    public function isLandlordStaff(): bool
    {
        return $this->isLandlord() && filled($this->managed_landlord_id);
    }

    public function isLandlordOwner(): bool
    {
        return $this->isLandlord() && blank($this->managed_landlord_id);
    }

    public function landlordAccountId(): string
    {
        return (string) ($this->managed_landlord_id ?: $this->id);
    }

    public function landlordAccount(): User
    {
        if (! $this->isLandlordStaff()) {
            return $this;
        }

        return $this->landlordAccountOwner()->firstOrFail();
    }

    public function landlordTeamUserIds(): array
    {
        $ownerId = $this->landlordAccountId();

        return User::query()
            ->whereKey($ownerId)
            ->orWhere('managed_landlord_id', $ownerId)
            ->pluck('id')
            ->all();
    }

    public function hasActiveServiceAccess(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isLandlord()) {
            if ($this->isLandlordStaff()) {
                $owner = $this->landlordAccountOwner;

                return (bool) ($owner?->is_active && $owner->hasActiveServiceAccess());
            }
            if (! $this->requires_subscription) {
                return true;
            }

            if ($this->service_paid_until || $this->subscription_started_at) {
                return $this->service_paid_until && $this->service_paid_until->isFuture();
            }

            return $this->trial_ends_at && $this->trial_ends_at->isFuture();
        }

        $hasActiveTenancy = $this->tenancies()
            ->where('is_active', true)
            ->with('unit.property.landlord')
            ->get()
            ->contains(function ($tenancy) {
                $landlord = $tenancy->unit?->property?->landlord;

                if (! $landlord) {
                    return false;
                }

                return ! $landlord->is_active || ! $landlord->hasActiveServiceAccess();
            });

        return ! $hasActiveTenancy;
    }

    /**
     * Database-level equivalent of the landlord-owner branch of hasActiveServiceAccess().
     * Used to filter listings (e.g. the public marketplace) without loading every model.
     */
    public function scopeWithActiveSubscriptionAccess(Builder $query): Builder
    {
        return $query->where(function (Builder $access) {
            $access->where('requires_subscription', false)
                ->orWhereNull('requires_subscription')
                ->orWhere('service_paid_until', '>', now())
                ->orWhere(function (Builder $trial) {
                    $trial->whereNull('service_paid_until')
                        ->whereNull('subscription_started_at')
                        ->where('trial_ends_at', '>', now());
                });
        });
    }
}
