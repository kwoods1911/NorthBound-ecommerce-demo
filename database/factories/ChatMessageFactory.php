<?php

namespace Database\Factories;

use App\Enums\ChatRole;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chat_conversation_id' => ChatConversation::factory(),
            'role' => ChatRole::User,
            'content' => fake()->sentence(),
            'tool_name' => null,
            'tool_payload' => null,
        ];
    }

    public function assistant(): static
    {
        return $this->state(fn () => [
            'role' => ChatRole::Assistant,
            'content' => fake()->sentence(),
        ]);
    }
}
