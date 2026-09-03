<?php

namespace App\Services\Ai\Data;

/**
 * A tool the model may call, described with a JSON-Schema parameter object.
 * The provider translates this into whatever its API expects.
 */
final class ToolDefinition
{
    /**
     * @param  array<string, mixed>  $parameters  JSON Schema for the arguments object
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $parameters,
    ) {}
}
