<?php

namespace App\Enums;

/**
 * The author of a chat message. Used both for persistence (cast on ChatMessage)
 * and as the provider-agnostic role in the AI abstraction layer.
 */
enum ChatRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
