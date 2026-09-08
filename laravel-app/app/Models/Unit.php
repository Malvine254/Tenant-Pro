<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Unit extends Model
{
    use HasUuids;

    protected $appends = ['currency', 'currency_symbol', 'rent_amount_formatted'];

    protected $fillable = ['property_id', 'unit_number', 'floor', 'bedrooms', 'rent_amount', 'status', 'image_urls', 'billing_overrides', 'bathrooms', 'deposit_amount', 'service_charge', 'other_move_in_cost', 'other_move_in_label', 'amenities', 'available_from', 'availability_confirmed_at'];

    protected $casts = [
        'image_urls' => 'array',
        'rent_amount' => 'decimal:2',
        'billing_overrides' => 'array',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'amenities' => 'array',
        'deposit_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'other_move_in_cost' => 'decimal:2',
        'available_from' => 'date',
        'availability_confirmed_at' => 'datetime',
    ];

    public function property() { return $this->belongsTo(Property::class); }

    protected static function booted(): void
    {
        static::saving(function (Unit $unit) {
            if ($unit->status !== 'AVAILABLE' || ($unit->isDirty('status') && ! $unit->isDirty('availability_confirmed_at'))) {
                $unit->availability_confirmed_at = null;
            }
        });
    }
    public function tenant() { return $this->hasOne(Tenant::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class); }
    public function invitations() { return $this->hasMany(Invitation::class); }

    public function getCurrencyAttribute(): string { return 'KES'; }

    /** Advertised amounts only; unknown fees stay unknown rather than becoming zero. */
    public function moveInCosts(): array
    {
        $billing = array_replace($this->property?->billing_settings ?? [], $this->billing_overrides ?? []);
        $items = [
            'First month rent' => $this->rent_amount,
            'Security deposit' => $this->deposit_amount,
            'Service charge (first month)' => $this->service_charge,
            'Water (first month)' => $billing['water_monthly_fee'] ?? null,
            'Garbage (first month)' => $billing['garbage_monthly_fee'] ?? null,
            'Other one-time charges'.($this->other_move_in_label ? ' ('.$this->other_move_in_label.')' : '') => $this->other_move_in_cost,
        ];
        $missing = in_array(null, $items, true);
        return ['items' => $items, 'complete' => ! $missing, 'total' => array_sum(array_map(fn ($amount) => (int) round((float) $amount * 100), $items)) / 100];
    }
    public function getCurrencySymbolAttribute(): string { return 'KSh'; }
    public function getRentAmountFormattedAttribute(): string { return 'KSh ' . number_format((float) $this->rent_amount, 2); }

    public function getBedroomsLabelAttribute(): ?string
    {
        if ($this->bedrooms === null) {
            return null;
        }

        return $this->bedrooms === 0 ? 'Studio' : $this->bedrooms.' bed'.($this->bedrooms > 1 ? 's' : '');
    }
}
