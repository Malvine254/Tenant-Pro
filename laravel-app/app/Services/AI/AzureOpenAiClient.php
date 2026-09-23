<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper around the Azure OpenAI Chat Completions REST API with tool-calling support.
 * Holds no business logic - purely transport plus configuration.
 */
class AzureOpenAiClient
{
    public function __construct(private readonly array $config = [])
    {
    }

    public function isConfigured(): bool
    {
        $config = $this->config();

        return (bool) $config['enabled']
            && filled($config['endpoint'])
            && filled($config['api_key'])
            && filled($config['deployment']);
    }

    /**
     * Send a chat-completions request. $messages and $tools follow the OpenAI/Azure schema.
     *
     * @return array{content: ?string, tool_calls: array, raw: array}
     */
    public function chat(array $messages, array $tools = []): array
    {
        $config = $this->config();

        if (! $this->isConfigured()) {
            throw new AzureOpenAiException('Azure OpenAI is not configured.');
        }

        $endpoint = $this->resourceOrigin((string) $config['endpoint']);
        $url = sprintf(
            '%s/openai/deployments/%s/chat/completions?api-version=%s',
            $endpoint,
            $config['deployment'],
            $config['api_version']
        );

        $payload = [
            'messages' => $messages,
            'temperature' => $config['temperature'],
            'max_completion_tokens' => $config['max_output_tokens'],
        ];

        if (! empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->send($url, $payload, $config);

        // Reasoning models (e.g. gpt-5-mini/o1-family) spend part of max_completion_tokens on
        // hidden internal reasoning before writing any visible answer. Retry once with a larger
        // budget if the response was cut off with no visible content, instead of ever silently
        // returning nothing to the tenant.
        if ($this->wasTruncatedWithNoContent($response)) {
            $payload['max_completion_tokens'] = max($payload['max_completion_tokens'] * 4, 4000);
            Log::warning('Azure OpenAI response was truncated with no visible content; retrying with a larger token budget.', [
                'original_max_completion_tokens' => $config['max_output_tokens'],
                'retry_max_completion_tokens' => $payload['max_completion_tokens'],
            ]);
            $response = $this->send($url, $payload, $config);
        }

        // Some model families (e.g. reasoning models) reject a non-default temperature and
        // report exactly which parameter is unsupported. Drop it and retry once rather than
        // requiring every caller to know per-model quirks.
        if ($response->status() === 400 && $this->rejectedParameter($response) === 'temperature') {
            unset($payload['temperature']);
            $response = $this->send($url, $payload, $config);
        }

        if ($response->failed()) {
            Log::error('Azure OpenAI returned an error response.', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            throw new AzureOpenAiException('Azure OpenAI returned an error (HTTP '.$response->status().').');
        }

        $body = $response->json();
        $message = data_get($body, 'choices.0.message', []);

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? [],
            'raw' => $body ?? [],
        ];
    }

    private function send(string $url, array $payload, array $config): \Illuminate\Http\Client\Response
    {
        try {
            return Http::withHeaders(['api-key' => $config['api_key']])
                ->timeout($config['request_timeout'])
                ->retry(2, 500, throw: false)
                ->post($url, $payload);
        } catch (Throwable $e) {
            Log::error('Azure OpenAI request failed to send.', ['error' => $e->getMessage()]);
            throw new AzureOpenAiException('Unable to reach the Azure OpenAI service.', previous: $e);
        }
    }

    private function rejectedParameter(\Illuminate\Http\Client\Response $response): ?string
    {
        return data_get($response->json(), 'error.param');
    }

    /**
     * True when the model stopped due to hitting its token limit and produced no tool calls and
     * no visible content - the classic reasoning-model symptom of spending the whole budget on
     * hidden reasoning tokens.
     */
    private function wasTruncatedWithNoContent(\Illuminate\Http\Client\Response $response): bool
    {
        if ($response->failed()) {
            return false;
        }

        $message = data_get($response->json(), 'choices.0.message', []);
        $finishReason = data_get($response->json(), 'choices.0.finish_reason');

        return $finishReason === 'length'
            && empty($message['tool_calls'])
            && trim((string) ($message['content'] ?? '')) === '';
    }

    private function config(): array
    {
        return $this->config ?: config('services.azure_openai', []);
    }

    /**
     * Reduce whatever endpoint format was configured (resource root, or a full path such as
     * ".../openai/v1/responses") down to just the resource origin, since we always build our
     * own Chat Completions path from the deployment name.
     */
    private function resourceOrigin(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? trim($endpoint, '/');
        $origin = "{$scheme}://{$host}";

        if (! empty($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }
}
