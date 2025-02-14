<?php

namespace Jmrashed\SlackNotifier\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a webhook URL is invalid.
 */
class WebhookUrlNotValid extends RuntimeException
{
    /**
     * Create a new instance of the exception.
     *
     * @param  string  $name
     * @param  string  $url
     * @return static
     */
    public static function make(string $name, string $url): self
    {
        return new self(
            "The name `{$name}` webhook contains an invalid URL `{$url}`. "
            . "Make sure you specify a valid URL in the `webhook_urls.{$name}` key "
            . "of the slack-notifier.php config file."
        );
    }
}
