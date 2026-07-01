<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invoice extends Model
{
    use HasUuids;

    protected $appends = [
        'currency',
        'currency_symbol',
        'amount_formatted',
        'penalty_amount_formatted',
        'total_amount_formatted',
        'paid_amount_formatted',
        'balance_amount',
        'balance_amount_formatted',
    ];

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

    public function getCurrencyAttribute(): string { return 'KES'; }
    public function getCurrencySymbolAttribute(): string { return 'KSh'; }
    public function getAmountFormattedAttribute(): string { return $this->formatKes($this->amount); }
    public function getPenaltyAmountFormattedAttribute(): string { return $this->formatKes($this->penalty_amount); }
    public function getTotalAmountFormattedAttribute(): string { return $this->formatKes($this->total_amount); }
    public function getPaidAmountFormattedAttribute(): string { return $this->formatKes($this->paid_amount); }
    public function getBalanceAmountAttribute(): float { return max(0, (float) $this->total_amount - (float) $this->paid_amount); }
    public function getBalanceAmountFormattedAttribute(): string { return $this->formatKes($this->balance_amount); }

    private function formatKes($amount): string
    {
        return 'KSh ' . number_format((float) $amount, 2);
    }
}
