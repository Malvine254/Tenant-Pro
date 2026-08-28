<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
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
        ];
    }

    public function role() { return $this->belongsTo(Role::class); }
    public function properties() { return $this->hasMany(Property::class, 'landlord_id'); }
    public function tenant() { return $this->hasOne(Tenant::class); }
    public function tenancies() { return $this->hasMany(Tenant::class); }
    public function invoices() { return $this->hasMany(Invoice::class, 'user_id'); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'tenant_id'); }
    public function appNotifications() { return $this->hasMany(Notification::class); }
    public function supportConversations() { return $this->hasMany(SupportConversation::class, 'tenant_user_id'); }

    public function isLandlord(): bool
    {
        return $this->role?->name === 'LANDLORD';
    }

    public function hasActiveServiceAccess(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isLandlord()) {
            if (!$this->requires_subscription) {
                return true;
            }

            if ($this->service_paid_until && $this->service_paid_until->isFuture()) {
                return true;
            }

            return $this->trial_ends_at && $this->trial_ends_at->isFuture();
        }

        $hasActiveTenancy = $this->tenancies()
            ->where('is_active', true)
            ->with('unit.property.landlord')
            ->get()
            ->contains(function ($tenancy) {
                $landlord = $tenancy->unit?->property?->landlord;

                if (!$landlord) {
                    return false;
                }

                return !$landlord->is_active || !$landlord->hasActiveServiceAccess();
            });

        return !$hasActiveTenancy;
    }
}
