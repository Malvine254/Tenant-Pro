<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ListingReport extends Model
{
    use HasUuids;

    public const REASONS = ['unavailable' => 'Home is no longer available', 'price' => 'Price or fees are incorrect', 'photos' => 'Photos or description are misleading', 'suspicious' => 'Suspicious payment request', 'other' => 'Something else'];
    protected $fillable = ['property_id', 'reason', 'details', 'email', 'status', 'resolved_at', 'resolved_by'];
    protected $casts = ['resolved_at' => 'datetime'];
    public function property() { return $this->belongsTo(Property::class); }
}
