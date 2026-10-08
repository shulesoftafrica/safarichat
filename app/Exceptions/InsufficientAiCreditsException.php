<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a business has no AI credits left for the work a conversation needs.
 *
 * This is not a transient failure: retrying the same conversation cannot succeed until the owner
 * tops up, so callers should mark it as permanently failed instead of re-queueing it.
 */
class InsufficientAiCreditsException extends Exception
{
}
