<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PropertyInspection extends Model
{
    use HasUuids;

    protected $fillable = [
        'property_id',
        'unit_id',
        'tenant_id',
        'type',
        'status',
        'inspection_date',
        'inspector_name',
        'meter_reading_water',
        'meter_reading_electricity',
        'checklist_data',
        'general_notes',
        'tenant_acknowledgement',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'meter_reading_water' => 'decimal:2',
        'meter_reading_electricity' => 'decimal:2',
        'checklist_data' => 'array',
    ];

    protected $appends = ['type_label', 'status_label'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'MOVE_IN' => 'Move-In Inspection',
            'MOVE_OUT' => 'Move-Out Inspection',
            'PERIODIC' => 'Periodic Routine Check',
            default => ucfirst(strtolower($this->type)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'COMPLETED' => 'Certified & Completed',
            'DRAFT' => 'Draft',
            default => ucfirst(strtolower($this->status)),
        };
    }
}
