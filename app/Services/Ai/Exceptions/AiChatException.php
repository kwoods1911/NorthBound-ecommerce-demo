<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

/**
 * Base failure for any AI chat provider. Callers catch this to fall back to a
 * friendly message rather than leaking a raw API error to the user.
 */
class AiChatException extends RuntimeException {}
