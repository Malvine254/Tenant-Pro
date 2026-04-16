<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'user_id', 'unit_id', 'billing_type', 'period_month', 'period_year',
        'issue_date', 'due_date', 'amount', 'penalty_amount', 'total_amount',
        'paid_amount', 'status', 'paid_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function tenant() { return $this->belongsTo(User::class, 'tenant_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
