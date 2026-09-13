<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PropertyExpense extends Model
{
    use HasUuids;

    protected $fillable = [
        'property_id',
        'unit_id',
        'category',
        'title',
        'amount',
        'expense_date',
        'receipt_photo_path',
        'notes',
        'recorded_by_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['amount_formatted', 'category_label'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function getAmountFormattedAttribute(): string
    {
        return 'KSh ' . number_format((float) $this->amount, 2);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'MAINTENANCE' => 'Maintenance & Repairs',
            'UTILITIES' => 'Bulk Utilities (Water/Power)',
            'REPAIR' => 'Structural Repairs',
            'COUNTY_RATES' => 'County Land Rates',
            'SALARY' => 'Caretaker / Staff Salary',
            'SECURITY' => 'Security & Guarding',
            default => ucfirst(strtolower($this->category)),
        };
    }
}
