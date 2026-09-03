<?php

namespace App\Services\Ai\Exceptions;

/**
 * The provider returned HTTP 429. Handled specially so the user sees a
 * "try again in a moment" message instead of a generic error.
 */
class AiRateLimitException extends AiChatException {}
