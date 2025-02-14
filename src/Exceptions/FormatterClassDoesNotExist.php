<?php

namespace Jmrashed\SlackNotifier\Exceptions;

use RuntimeException;

/**
 * Exception thrown when the configured formatter class does not exist.
 */
class FormatterClassDoesNotExist extends RuntimeException
{
    /**
     * Create a new instance of the exception.
     *
     * @param  string|null  $name  The name of the formatter class that does not exist
     * @return static
     */
    public static function make(?string $name): self
    {
        return new self(
            "The configured formatter class '{$name}' does not exist. Make sure you specify a valid class name in the 'formatter' key of the slack-notifier.php config file."
        );
    }
}
