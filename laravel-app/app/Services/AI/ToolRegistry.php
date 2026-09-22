<?php

namespace App\Services\AI;

use App\Models\SupportConversation;
use App\Models\User;
use App\Services\AI\Tools\ChatTool;
use App\Services\AI\Tools\CreateMaintenanceRequestTool;
use App\Services\AI\Tools\EscalateToHumanTool;
use App\Services\AI\Tools\GetInvoicesTool;
use App\Services\AI\Tools\GetMaintenanceRequestsTool;
use App\Services\AI\Tools\GetPaymentHistoryTool;
use App\Services\AI\Tools\GetTenancyDetailsTool;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Central registry of backend capabilities exposed to the AI assistant.
 * Adding a new capability means implementing ChatTool and listing it in build().
 */
class ToolRegistry
{
    /** @var ChatTool[] */
    private array $tools;

    public function __construct(SupportConversation $conversation)
    {
        $this->tools = [
            new GetTenancyDetailsTool(),
            new GetInvoicesTool(),
            new GetPaymentHistoryTool(),
            new GetMaintenanceRequestsTool(),
            new CreateMaintenanceRequestTool(),
            new EscalateToHumanTool($conversation),
        ];
    }

    /** Tool definitions (Azure/OpenAI function-calling schema) for the tools this user may call. */
    public function definitionsFor(User $user): array
    {
        return collect($this->tools)
            ->filter(fn (ChatTool $tool) => $tool->authorize($user))
            ->map(fn (ChatTool $tool) => [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $tool->parameters(),
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * Execute a named tool for the given user, enforcing authorization.
     * Never throws for expected failures - returns a structured error envelope instead.
     */
    public function call(string $name, array $arguments, User $user): array
    {
        $tool = collect($this->tools)->first(fn (ChatTool $tool) => $tool->name() === $name);

        if (! $tool) {
            return ['ok' => false, 'error' => "Unknown tool: {$name}"];
        }

        if (! $tool->authorize($user)) {
            return ['ok' => false, 'error' => 'You are not authorized to use this capability.'];
        }

        try {
            return $tool->execute($arguments, $user);
        } catch (Throwable $e) {
            Log::error('AI tool execution failed.', ['tool' => $name, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'This capability is temporarily unavailable. Please try again shortly.'];
        }
    }
}
