<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupportMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'conversation_id', 'sender_id', 'topic', 'body',
        'attachment_name', 'attachment_uri', 'attachment_mime_type', 'attachment_size',
        'thumbnail_url', 'duration', 'message_type', 'upload_status', 'client_message_id',
        'is_from_tenant', 'status', 'delivered_at', 'read_at',
    ];

    protected $casts = ['is_from_tenant' => 'boolean', 'delivered_at' => 'datetime', 'read_at' => 'datetime'];

    public function conversation() { return $this->belongsTo(SupportConversation::class); }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
}
