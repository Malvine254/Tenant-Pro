<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'code', 'phone_number', 'property_id', 'unit_id', 'sent_by_id',
        'status', 'expires_at', 'accepted_at', 'sent_via', 'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function property() { return $this->belongsTo(Property::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function sentBy() { return $this->belongsTo(User::class, 'sent_by_id'); }
}
