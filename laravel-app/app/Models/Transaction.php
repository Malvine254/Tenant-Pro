<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuids;

    protected $fillable = ['payment_id', 'amount', 'type', 'description'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payment() { return $this->belongsTo(Payment::class); }
}
