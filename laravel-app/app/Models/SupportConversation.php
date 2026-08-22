<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupportConversation extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_user_id', 'landlord_user_id', 'property_id', 'subject', 'topic', 'is_open'];

    protected $casts = ['is_open' => 'boolean'];

    public function tenant() { return $this->belongsTo(User::class, 'tenant_user_id'); }
    public function landlord() { return $this->belongsTo(User::class, 'landlord_user_id'); }
    public function property() { return $this->belongsTo(Property::class, 'property_id'); }
    public function messages() { return $this->hasMany(SupportMessage::class, 'conversation_id'); }
}
