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
        ];
    }

    public function role() { return $this->belongsTo(Role::class); }
    public function properties() { return $this->hasMany(Property::class, 'landlord_id'); }
    public function tenant() { return $this->hasOne(Tenant::class); }
    public function invoices() { return $this->hasMany(Invoice::class, 'user_id'); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class, 'tenant_id'); }
    public function appNotifications() { return $this->hasMany(Notification::class); }
    public function supportConversations() { return $this->hasMany(SupportConversation::class, 'tenant_user_id'); }
}
