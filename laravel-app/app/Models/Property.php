<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Property extends Model
{
    use HasUuids;

    protected $fillable = [
        'landlord_id', 'name', 'description', 'cover_image_url',
        'address_line', 'city', 'state', 'country',
    ];

    public function landlord() { return $this->belongsTo(User::class, 'landlord_id'); }
    public function units() { return $this->hasMany(Unit::class); }
    public function invitations() { return $this->hasMany(Invitation::class); }
}
