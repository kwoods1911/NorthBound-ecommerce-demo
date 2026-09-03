<?php

namespace App\Services;

use App\Enums\ChatRole;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\Data\AiChatRequest;
use App\Services\Ai\Data\AiMessage;
use App\Services\Ai\Data\AiToolCall;
use App\Services\Ai\Exceptions\AiChatException;
use App\Services\Ai\Exceptions\AiRateLimitException;
use App\Services\Ai\Tools\ToolRegistry;
use Illuminate\Support\Str;

/**
 * Runs the chatbot's converse → call tools → converse loop, persisting every
 * turn to the conversation. Providers and tools are injected; this class owns
 * only the loop and the system prompt.
 */
class ChatService
{
    private const MAX_TOOL_ITERATIONS = 4;

    /** Product cards gathered from tool results during the current send(). */
    private array $collectedProducts = [];

    public function __construct(
        private readonly AiChatProvider $provider,
        private readonly ToolRegistry $tools,
    ) {}

    /**
     * The conversation for the current visitor — keyed by a random token kept in
     * the session so it survives login (mirrors CartService's guest token).
     */
    public function conversationForCurrentSession(): ChatConversation
    {
        $token = session('chat_token');

        if (! $token) {
            $token = (string) Str::uuid();
            session(['chat_token' => $token]);
        }

        return ChatConversation::firstOrCreate(
            ['session_id' => $token],
            ['user_id' => auth()->id()],
        );
    }

    /**
     * Answer one user message. Always returns the assistant ChatMessage to show
     * (a friendly fallback if the provider is unavailable).
     */
    public function send(ChatConversation $conversation, string $message): ChatMessage
    {
        $this->collectedProducts = [];

        $conversation->messages()->create([
            'role' => ChatRole::User,
            'content' => $message,
        ]);

        try {
            return $this->runLoop($conversation);
        } catch (AiRateLimitException) {
            return $this->reply(
                $conversation,
                "I'm getting a lot of questions right now — give me a moment and try again.",
            );
        } catch (AiChatException $e) {
            report($e);

            return $this->reply(
                $conversation,
                "Sorry, I can't reach the assistant just now. Please try again in a little while.",
            );
        }
    }

    private function runLoop(ChatConversation $conversation): ChatMessage
    {
        for ($iteration = 0; $iteration < self::MAX_TOOL_ITERATIONS; $iteration++) {
            $response = $this->provider->chat(new AiChatRequest(
                systemPrompt: $this->systemPrompt(),
                messages: $this->transcript($conversation),
                tools: $this->tools->definitions(),
            ));

            if (! $response->hasToolCalls()) {
                return $this->reply(
                    $conversation,
                    $response->text ?? 'I can help with questions about our products, shipping and returns — what would you like to know?',
                );
            }

            $this->recordToolCalls($conversation, $response->toolCalls, $response->text);

            foreach ($response->toolCalls as $call) {
                $result = $this->tools->dispatch($call);
                $this->collectProducts($result);

                $conversation->messages()->create([
                    'role' => ChatRole::Tool,
                    'content' => null,
                    'tool_name' => $call->name,
                    'tool_payload' => ['tool_call_id' => $call->id] + $result,
                ]);
            }
        }

        return $this->reply(
            $conversation,
            "I looked into that but couldn't pin it down. Could you rephrase or add a little more detail?",
        );
    }

    /**
     * Persist the assistant's tool-call turn so the next request replays the
     * functionCall that each functionResponse answers.
     *
     * @param  array<int, AiToolCall>  $calls
     */
    private function recordToolCalls(ChatConversation $conversation, array $calls, ?string $text): void
    {
        $conversation->messages()->create([
            'role' => ChatRole::Assistant,
            'content' => $text,
            'tool_payload' => [
                'tool_calls' => array_map(fn (AiToolCall $call) => [
                    'id' => $call->id,
                    'name' => $call->name,
                    'arguments' => $call->arguments,
                    'signature' => $call->signature,
                ], $calls),
            ],
        ]);
    }

    private function reply(ChatConversation $conversation, string $text): ChatMessage
    {
        return $conversation->messages()->create([
            'role' => ChatRole::Assistant,
            'content' => $text,
            'tool_payload' => $this->collectedProducts === []
                ? null
                : ['products' => array_values($this->collectedProducts)],
        ]);
    }

    /**
     * Rebuild the provider-agnostic transcript from persisted messages.
     *
     * @return array<int, AiMessage>
     */
    private function transcript(ChatConversation $conversation): array
    {
        return $conversation->messages()->get()
            ->map(fn (ChatMessage $message) => $this->toAiMessage($message))
            ->all();
    }

    private function toAiMessage(ChatMessage $message): AiMessage
    {
        return match ($message->role) {
            ChatRole::User => AiMessage::user($message->content ?? ''),
            ChatRole::Assistant => AiMessage::assistant(
                $message->content,
                array_map(
                    fn (array $call) => new AiToolCall(
                        $call['id'] ?? $call['name'],
                        $call['name'],
                        $call['arguments'] ?? [],
                        $call['signature'] ?? null,
                    ),
                    $message->tool_payload['tool_calls'] ?? [],
                ),
            ),
            ChatRole::Tool => AiMessage::tool(
                $message->tool_name ?? 'unknown',
                $this->toolResultForModel($message->tool_payload ?? []),
                $message->tool_payload['tool_call_id'] ?? null,
            ),
        };
    }

    /**
     * Strip bookkeeping keys before handing a tool result back to the model.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function toolResultForModel(array $payload): array
    {
        unset($payload['tool_call_id']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function collectProducts(array $result): void
    {
        foreach ($result['products'] ?? [] as $product) {
            if (isset($product['id'])) {
                $this->collectedProducts[$product['id']] = $product;
            }
        }
    }

    private function systemPrompt(): string
    {
        $support = config('shop.support');

        return <<<PROMPT
        You are the shopping assistant for {$support['store_name']}, an online store that sells travel gear and luggage (suitcases, carry-ons, weekender bags, packing cubes and travel accessories).

        What you do:
        - Answer questions about products using the searchProducts and checkStock tools. Always call searchProducts before describing or pricing anything — never invent products, prices or specs.
        - Answer questions about shipping and returns using only the policy text below.
        - Keep replies short, friendly and in plain text. Do not use markdown, bullet characters or headings. Prices are in US dollars.

        What you cannot do:
        - You cannot add items to the cart, place orders, look up existing orders, apply discounts, or take any other action.
        - You cannot help with topics unrelated to this store's products or policies. Politely decline and steer back to travel gear.

        Shipping policy: {$support['shipping']}

        Returns policy: {$support['returns']}
        PROMPT;
    }
}
