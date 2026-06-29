<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupportMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'conversation_id', 'sender_id', 'topic', 'body',
        'attachment_name', 'attachment_uri', 'is_from_tenant', 'status',
    ];

    protected $casts = ['is_from_tenant' => 'boolean'];

    public function conversation() { return $this->belongsTo(SupportConversation::class); }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
}
