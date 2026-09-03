<?php

namespace App\Models;

use App\Enums\ChatRole;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'chat_conversation_id',
        'role',
        'content',
        'tool_name',
        'tool_payload',
    ];

    protected function casts(): array
    {
        return [
            'role' => ChatRole::class,
            'tool_payload' => 'array',
        ];
    }

    /**
     * Messages shown in the chat UI — user prompts and assistant replies with
     * text. Intermediate tool-call and tool-result turns are internal.
     *
     * @param  Builder<ChatMessage>  $query
     * @return Builder<ChatMessage>
     */
    public function scopeDisplayable(Builder $query): Builder
    {
        return $query
            ->whereIn('role', [ChatRole::User, ChatRole::Assistant])
            ->whereNotNull('content');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }
}
