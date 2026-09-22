<?php

namespace App\Services\AI\Tools;

use App\Models\SupportConversation;
use App\Models\User;
use App\Services\TenantAppNotificationService;

/**
 * The only tool that hands a conversation off to a human. Must only be invoked when the
 * assistant has determined the tenant explicitly asked for escalation/human support -
 * this is enforced through the system prompt's instructions to the model, not here.
 */
class EscalateToHumanTool implements ChatTool
{
    public function __construct(private readonly SupportConversation $conversation)
    {
    }

    public function name(): string
    {
        return 'escalate_to_human';
    }

    public function description(): string
    {
        return 'Hand this conversation off to the human property manager/landlord. Only call this when '
            .'the tenant has explicitly asked to speak to a human, contact support, or escalate the issue. '
            .'Do not call this automatically just because a question could not be answered.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reason' => [
                    'type' => 'string',
                    'description' => 'A brief, factual summary of why the tenant asked to be escalated, for the property manager.',
                ],
            ],
            'required' => ['reason'],
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->role?->name === 'TENANT';
    }

    public function execute(array $arguments, User $user): array
    {
        $reason = trim((string) ($arguments['reason'] ?? 'Tenant requested human support.'));

        $this->conversation->update([
            'escalated_at' => now(),
            'escalation_reason' => \Illuminate\Support\Str::limit($reason, 480, ''),
        ]);

        $this->conversation->loadMissing(['tenant', 'landlord']);
        app(TenantAppNotificationService::class)->notify(
            $this->conversation->landlord,
            'SUPPORT_ESCALATION',
            'Tenant requested human support',
            "{$this->conversation->tenant?->name} asked to speak with support: {$reason}",
            ['conversation_id' => $this->conversation->id]
        );

        return [
            'ok' => true,
            'data' => ['escalated' => true],
            'message' => 'This conversation has been escalated to the property manager, who will follow up here.',
        ];
    }
}
