<?php

namespace App\Services\Ai\Data;

/**
 * A single tool invocation requested by the model. Provider-agnostic: the
 * concrete provider is responsible for mapping its own wire format onto this.
 */
final class AiToolCall
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  string|null  $signature  opaque provider token that must be replayed
     *                                  with the call on the next request (Gemini
     *                                  thought signatures); null for providers
     *                                  that don't use one
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $arguments = [],
        public readonly ?string $signature = null,
    ) {}
}
