<?php

namespace App\Services\Ai;

use App\Services\Ai\Data\AiChatRequest;
use App\Services\Ai\Data\AiChatResponse;
use App\Services\Ai\Exceptions\AiChatException;

/**
 * Provider-agnostic contract for a tool-calling chat model. The storefront only
 * ever depends on this interface — no vendor SDK or wire format leaks past the
 * concrete implementation bound in AiServiceProvider.
 */
interface AiChatProvider
{
    /**
     * Run one completion. Returns free text or tool calls to execute.
     *
     * @throws AiChatException on any provider failure (429 becomes AiRateLimitException)
     */
    public function chat(AiChatRequest $request): AiChatResponse;
}
