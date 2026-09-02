<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PropertyEnquiry extends Model
{
    use HasUuids;

    protected $fillable = [
        'property_id', 'unit_id', 'name', 'email', 'phone_number', 'message',
        'status', 'source_ip_hash',
    ];

    protected $hidden = ['source_ip_hash'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
