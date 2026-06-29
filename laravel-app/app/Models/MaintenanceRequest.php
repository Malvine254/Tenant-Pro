<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'unit_id', 'reported_by_id', 'assigned_to_id',
        'title', 'description', 'priority', 'status', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function tenant() { return $this->belongsTo(User::class, 'tenant_id'); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function reportedBy() { return $this->belongsTo(User::class, 'reported_by_id'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to_id'); }
}
