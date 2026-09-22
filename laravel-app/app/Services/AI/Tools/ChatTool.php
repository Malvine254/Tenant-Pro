<?php

namespace App\Services\AI\Tools;

use App\Models\User;

/**
 * Contract for a single backend capability exposed to the AI assistant via tool-calling.
 * Implementations must enforce their own authorization and tenant scoping - never trust
 * identifiers supplied in $arguments as proof of access.
 */
interface ChatTool
{
    public function name(): string;

    public function description(): string;

    /** JSON schema for the tool's parameters, per the OpenAI/Azure function-calling spec. */
    public function parameters(): array;

    /** Whether the authenticated user may invoke this tool at all. */
    public function authorize(User $user): bool;

    /**
     * Execute the tool. Must return a structured, JSON-serializable result.
     * Implementations should catch their own domain errors and return
     * ['ok' => false, 'error' => '...'] rather than throwing where possible.
     */
    public function execute(array $arguments, User $user): array;
}
