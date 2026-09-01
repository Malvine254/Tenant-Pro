<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function operationLabel(): string
    {
        $routeAction = Str::afterLast($this->action, '.');

        return match ($routeAction) {
            'store' => 'Created',
            'update' => 'Updated',
            'destroy' => 'Deleted',
            'cancel' => 'Cancelled',
            'resend' => 'Resent',
            'status' => 'Changed status',
            'record' => 'Recorded',
            'reply' => 'Replied',
            'toggle' => 'Changed',
            'read' => 'Marked read',
            'read-all' => 'Marked all read',
            'password' => 'Changed password',
            'account' => 'Updated profile',
            'daraja' => 'Updated Daraja API',
            'maintenance' => 'Updated maintenance mode',
            'payment' => 'Updated payment settings',
            'tenant-preferences' => 'Updated tenant preferences',
            default => match (strtoupper($this->method)) {
                'POST' => 'Submitted',
                'PUT', 'PATCH' => 'Updated',
                'DELETE' => 'Deleted',
                default => 'Viewed',
            },
        };
    }

    public function operationDescription(): string
    {
        $resource = $this->target_type
            ? Str::headline($this->target_type)
            : Str::headline(explode('.', $this->action)[1] ?? 'administrative record');
        $identifier = $this->target_id ? ' #'.Str::limit($this->target_id, 18, '') : '';

        return $this->operationLabel().' '.$resource.$identifier;
    }
}
