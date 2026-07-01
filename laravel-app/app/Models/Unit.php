<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Unit extends Model
{
    use HasUuids;

    protected $appends = ['currency', 'currency_symbol', 'rent_amount_formatted'];

    protected $fillable = ['property_id', 'unit_number', 'floor', 'rent_amount', 'status', 'image_urls'];

    protected $casts = [
        'image_urls' => 'array',
        'rent_amount' => 'decimal:2',
    ];

    public function property() { return $this->belongsTo(Property::class); }
    public function tenant() { return $this->hasOne(Tenant::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class); }
    public function invitations() { return $this->hasMany(Invitation::class); }

    public function getCurrencyAttribute(): string { return 'KES'; }
    public function getCurrencySymbolAttribute(): string { return 'KSh'; }
    public function getRentAmountFormattedAttribute(): string { return 'KSh ' . number_format((float) $this->rent_amount, 2); }
}
