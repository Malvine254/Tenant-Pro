<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'actor_role',
        'action',
        'method',
        'path',
        'status_code',
        'target_type',
        'target_id',
        'request_id',
        'ip_address',
        'user_agent',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
