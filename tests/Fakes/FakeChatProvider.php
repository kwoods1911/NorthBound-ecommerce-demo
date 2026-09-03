<?php

namespace Tests\Fakes;

use App\Services\Ai\AiChatProvider;
use App\Services\Ai\Data\AiChatRequest;
use App\Services\Ai\Data\AiChatResponse;

/**
 * Scripted stand-in for {@see AiChatProvider}. Queue up the responses the model
 * should return, one per turn; the request for each turn is captured for
 * assertions. A queued Throwable is thrown instead of returned.
 */
class FakeChatProvider implements AiChatProvider
{
    /** @var array<int, AiChatResponse|\Throwable> */
    public array $queue = [];

    /** @var array<int, AiChatRequest> */
    public array $requests = [];

    public function chat(AiChatRequest $request): AiChatResponse
    {
        $this->requests[] = $request;

        $next = array_shift($this->queue) ?? new AiChatResponse('(no scripted response)');

        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    public function queue(AiChatResponse|\Throwable ...$responses): self
    {
        $this->queue = array_merge($this->queue, $responses);

        return $this;
    }

    public function callCount(): int
    {
        return count($this->requests);
    }

    public function lastRequest(): ?AiChatRequest
    {
        return end($this->requests) ?: null;
    }
}
