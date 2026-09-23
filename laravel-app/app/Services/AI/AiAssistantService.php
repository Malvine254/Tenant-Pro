<?php

namespace App\Services\AI;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates the full Azure OpenAI tool-calling lifecycle for a tenant support conversation:
 * builds context, submits to the model, executes any requested tools, loops until the model
 * produces a final answer, and persists the result as a SupportMessage.
 *
 * This is the only place tool execution and model calls are wired together, so behavior stays
 * centralized rather than scattered across controllers.
 */
class AiAssistantService
{
    public function __construct(
        private readonly AzureOpenAiClient $client,
        private readonly SystemPrompt $systemPrompt,
    ) {
    }

    /**
     * Generate and persist the assistant's reply to the tenant's latest message, if applicable.
     * Returns null when the AI should not respond (disabled, escalated, not configured, or a tenant
     * is not the one messaging).
     */
    public function respond(SupportConversation $conversation, User $tenant): ?SupportMessage
    {
        if (! $this->client->isConfigured() || ! $conversation->ai_enabled || $conversation->escalated_at) {
            return null;
        }

        if ($tenant->role?->name !== 'TENANT') {
            return null;
        }

        try {
            $this->setTyping($conversation, true);

            return $this->generateAndPersist($conversation, $tenant);
        } catch (Throwable $e) {
            Log::error('AI assistant could not produce or persist a reply.', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            $this->setTyping($conversation, false);
        }
    }

    /**
     * Surface a "typing" state while the assistant is composing, using a per-conversation cache
     * key so it is never confused with a real human property manager typing (that uses its own
     * separate global flag set only by the admin web inbox's own textarea).
     */
    private function setTyping(SupportConversation $conversation, bool $typing): void
    {
        $conversationKey = 'chat:ai:typing:'.$conversation->id;

        if ($typing) {
            Cache::put($conversationKey, true, now()->addSeconds(60));
        } else {
            Cache::forget($conversationKey);
        }
    }

    private function generateAndPersist(SupportConversation $conversation, User $tenant): ?SupportMessage
    {
        $config = config('services.azure_openai');
        $registry = new ToolRegistry($conversation);
        $tools = $registry->definitionsFor($tenant);

        $messages = $this->buildMessages($conversation, $tenant, $config['context_message_limit'] ?? 20);
        $toolTrace = [];

        try {
            $iterations = 0;
            $maxIterations = max(1, (int) ($config['max_tool_iterations'] ?? 5));

            while ($iterations < $maxIterations) {
                $iterations++;
                $result = $this->client->chat($messages, $tools);

                if (empty($result['tool_calls'])) {
                    $content = trim((string) ($result['content'] ?? ''));

                    if ($content === '') {
                        // The model returned no visible content (commonly a reasoning model that
                        // exhausted its token budget on hidden reasoning). Never leave the tenant
                        // with silence - explain the limitation instead.
                        Log::warning('AI assistant received an empty response from the model.', [
                            'conversation_id' => $conversation->id,
                            'finish_reason' => data_get($result, 'raw.choices.0.finish_reason'),
                        ]);

                        return $this->persistReply(
                            $conversation,
                            "I wasn't able to put together a complete answer to that. Could you ask "
                                .'it a bit more simply, or in smaller parts?',
                            $toolTrace
                        );
                    }

                    return $this->persistReply($conversation, $content, $toolTrace);
                }

                // The model asked for one or more tools. Record its request, execute each
                // tool, then feed the results back so the model can continue reasoning.
                $messages[] = ['role' => 'assistant', 'content' => null, 'tool_calls' => $result['tool_calls']];

                foreach ($result['tool_calls'] as $toolCall) {
                    $functionName = data_get($toolCall, 'function.name', '');
                    $arguments = json_decode(data_get($toolCall, 'function.arguments', '{}'), true) ?: [];

                    $toolResult = $registry->call($functionName, $arguments, $tenant);
                    $toolTrace[] = ['name' => $functionName, 'arguments' => $arguments, 'result' => $toolResult];

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => data_get($toolCall, 'id'),
                        'content' => json_encode($toolResult),
                    ];
                }
            }

            Log::warning('AI assistant hit the max tool-call iteration limit.', ['conversation_id' => $conversation->id]);

            return $this->persistReply(
                $conversation,
                "I gathered some information but couldn't finish putting together a complete answer. "
                    .'Could you rephrase your question, or ask about one thing at a time?',
                $toolTrace
            );
        } catch (Throwable $e) {
            Log::error('AI assistant failed to generate a reply.', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return $this->persistReply(
                $conversation,
                "I'm having trouble accessing that information right now. Please try again shortly, "
                    .'or ask to speak with your property manager if this is urgent.',
                $toolTrace
            );
        }
    }

    private function buildMessages(SupportConversation $conversation, User $tenant, int $limit): array
    {
        $conversation->loadMissing(['property', 'landlord']);

        $history = SupportMessage::where('conversation_id', $conversation->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt->build($tenant, $conversation)],
        ];

        foreach ($history as $message) {
            /** @var SupportMessage $message */
            $body = trim((string) $message->body);
            if ($body === '' && $message->attachment_name) {
                $body = '[Attachment: '.$message->attachment_name.']';
            }

            $messages[] = [
                'role' => $message->is_from_tenant ? 'user' : 'assistant',
                'content' => $body,
            ];
        }

        return $messages;
    }

    private function persistReply(SupportConversation $conversation, string $content, array $toolTrace): SupportMessage
    {
        $senderId = $conversation->landlord_user_id ?: $conversation->property?->landlord_id;

        if (! $senderId) {
            // support_messages.sender_id is a required foreign key; without a landlord to attribute
            // the reply to, we cannot persist it. This should not happen for properly linked conversations.
            Log::error('AI assistant could not persist a reply: no landlord to attribute the message to.', [
                'conversation_id' => $conversation->id,
            ]);

            throw new AzureOpenAiException('No landlord is linked to this conversation.');
        }

        return SupportMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'topic' => $conversation->topic ?: 'General',
            'body' => $content,
            'message_type' => 'text',
            'is_from_tenant' => false,
            'is_ai' => true,
            'tool_calls' => $toolTrace ?: null,
            'status' => 'SENT',
        ]);
    }
}
