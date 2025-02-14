<?php

namespace Jmrashed\SlackNotifier\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a webhook fails to send.
 */
class WebhookSendFail extends RuntimeException
{
    /**
     * Create a new instance of the exception.
     *
     * @param  Throwable  $exception
     * @return static
     */
    public static function make(Throwable $exception): self
    {
        return new self(
            get_class($exception) . ': ' . $exception->getMessage(),
            0, // Set the code to 0, as we are using the exception message for details
            $exception->getPrevious()
        );
    }
}
