<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatConversation>
 */
class ChatConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id' => (string) Str::uuid(),
            'user_id' => null,
        ];
    }
}
