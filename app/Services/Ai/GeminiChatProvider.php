<?php

namespace App\Services\Ai;

use App\Enums\ChatRole;
use App\Services\Ai\Data\AiChatRequest;
use App\Services\Ai\Data\AiChatResponse;
use App\Services\Ai\Data\AiMessage;
use App\Services\Ai\Data\AiToolCall;
use App\Services\Ai\Data\ToolDefinition;
use App\Services\Ai\Exceptions\AiChatException;
use App\Services\Ai\Exceptions\AiRateLimitException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The one and only Gemini-aware class. Translates the provider-agnostic DTOs to
 * and from the `generateContent` wire format. The model is configurable
 * (services.gemini.model); `gemini-flash-latest` by default.
 */
final class GeminiChatProvider implements AiChatProvider
{
    /** Google-side hiccups worth one quick retry before giving up. */
    private const TRANSIENT_STATUSES = [500, 502, 503, 504];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
        private readonly int $timeout = 20,
        private readonly int $retries = 2,
        private readonly int $retryBackoffMs = 300,
    ) {}

    public function chat(AiChatRequest $request): AiChatResponse
    {
        $payload = [
            'systemInstruction' => ['parts' => [['text' => $request->systemPrompt]]],
            'contents' => $this->contents($request->messages),
        ];

        if ($request->tools !== []) {
            $payload['tools'] = [['functionDeclarations' => $this->functionDeclarations($request->tools)]];
            $payload['toolConfig'] = ['functionCallingConfig' => ['mode' => 'AUTO']];
        }

        $response = $this->post($payload);

        if ($response->status() === 429) {
            throw new AiRateLimitException('Gemini rate limit reached.');
        }

        if ($response->failed()) {
            $detail = $response->json('error.message') ?: $response->body();

            throw new AiChatException("Gemini request failed ({$response->status()}): {$detail}");
        }

        return $this->parse($response->json() ?? []);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(array $payload): Response
    {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";

        for ($attempt = 0; ; $attempt++) {
            $response = Http::asJson()
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->timeout($this->timeout)
                ->post($url, $payload);

            if ($attempt >= $this->retries || ! in_array($response->status(), self::TRANSIENT_STATUSES, true)) {
                return $response;
            }

            if ($this->retryBackoffMs > 0) {
                usleep($this->retryBackoffMs * 1000 * ($attempt + 1));
            }
        }
    }

    /**
     * @param  array<int, AiMessage>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function contents(array $messages): array
    {
        return array_map(fn (AiMessage $message) => match ($message->role) {
            ChatRole::User => [
                'role' => 'user',
                'parts' => [['text' => $message->content ?? '']],
            ],
            ChatRole::Assistant => [
                'role' => 'model',
                'parts' => $this->assistantParts($message),
            ],
            ChatRole::Tool => [
                'role' => 'user',
                'parts' => [[
                    'functionResponse' => array_filter([
                        'id' => $message->toolCallId,
                        'name' => $message->toolName,
                        'response' => (object) ($message->toolResult ?? []),
                    ], fn ($value) => $value !== null),
                ]],
            ],
        }, $messages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assistantParts(AiMessage $message): array
    {
        $parts = [];

        if ($message->content !== null && $message->content !== '') {
            $parts[] = ['text' => $message->content];
        }

        foreach ($message->toolCalls as $call) {
            $functionCall = ['name' => $call->name, 'args' => (object) $call->arguments];

            // Echo back the call id so Gemini can pair it with our
            // functionResponse; without it the model re-issues the same call.
            if ($call->id !== '') {
                $functionCall['id'] = $call->id;
            }

            $part = ['functionCall' => $functionCall];

            // Gemini 3 rejects a follow-up request unless the thought signature
            // that came back with the call is replayed on the same part.
            if ($call->signature !== null && $call->signature !== '') {
                $part['thoughtSignature'] = $call->signature;
            }

            $parts[] = $part;
        }

        return $parts === [] ? [['text' => '']] : $parts;
    }

    /**
     * @param  array<int, ToolDefinition>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function functionDeclarations(array $tools): array
    {
        return array_map(fn (ToolDefinition $tool) => [
            'name' => $tool->name,
            'description' => $tool->description,
            'parameters' => $tool->parameters,
        ], $tools);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function parse(array $body): AiChatResponse
    {
        $candidate = $body['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $text = '';
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }

            if (isset($part['functionCall']['name'])) {
                $call = $part['functionCall'];
                $toolCalls[] = new AiToolCall(
                    id: $call['id'] ?? $call['name'].'-'.count($toolCalls),
                    name: $call['name'],
                    arguments: (array) ($call['args'] ?? []),
                    signature: $part['thoughtSignature'] ?? null,
                );
            }
        }

        $text = trim($text);

        return new AiChatResponse(
            text: $text === '' ? null : $text,
            toolCalls: $toolCalls,
            finishReason: $candidate['finishReason'] ?? null,
        );
    }
}
