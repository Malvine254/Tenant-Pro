<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasUuids;

    protected $appends = ['currency', 'currency_symbol', 'amount_formatted'];

    protected $fillable = [
        'invoice_id', 'amount', 'method', 'payment_phone', 'mpesa_receipt',
        'status', 'checkout_request_id', 'reference', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }

    public function getCurrencyAttribute(): string { return 'KES'; }
    public function getCurrencySymbolAttribute(): string { return 'KSh'; }
    public function getAmountFormattedAttribute(): string { return 'KSh ' . number_format((float) $this->amount, 2); }
}
