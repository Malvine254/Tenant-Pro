<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'payment_id', 'amount', 'type', 'description', 'external_reference',
        'checkout_request_id', 'merchant_request_id', 'processed_at', 'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function payment() { return $this->belongsTo(Payment::class); }
}
