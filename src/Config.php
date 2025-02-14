<?php

namespace Jmrashed\SlackNotifier;

use Jmrashed\SlackNotifier\Exceptions\FormatterClassDoesNotExist;
use Jmrashed\SlackNotifier\Exceptions\WebhookUrlNotValid;

class Config
{
    /**
     * Retrieves the Slack notifier formatter class instance.
     *
     * @param array $arguments The arguments to pass when creating the formatter instance.
     * @return SlackNotifierFormatter The formatter instance.
     * @throws FormatterClassDoesNotExist If the formatter class doesn't exist or is not properly defined in the configuration.
     */
    public static function getFormatter(array $arguments = []): SlackNotifierFormatter
    {
        // Retrieve the formatter class name from the config.
        $formatterClass = config('slack-notifier.formatter');

        // Check if the formatter class is defined and exists.
        if (is_null($formatterClass) || ! class_exists($formatterClass)) {
            throw FormatterClassDoesNotExist::make($formatterClass);
        }

        // Return the instantiated formatter class using dependency injection.
        return app($formatterClass, $arguments);
    }

    /**
     * Retrieves the webhook URL based on the provided name.
     *
     * @param string $name The name of the webhook URL to fetch from the configuration.
     * @return string|null The webhook URL or null if not found.
     * @throws WebhookUrlNotValid If the URL is not valid according to the filter.
     */
    public static function getWebhookUrl(string $name): ?string
    {
        // If the provided name is already a valid URL, return it.
        if (filter_var($name, FILTER_VALIDATE_URL)) {
            return $name;
        }

        // Retrieve the webhook URL from the configuration based on the given name.
        $url = config("slack-notifier.webhook_urls.{$name}");

        // If no URL is found in the config, return null.
        if (! $url) {
            return null;
        }

        // Validate the URL and throw an exception if it is not a valid URL.
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw WebhookUrlNotValid::make($name, $url);
        }

        // Return the valid webhook URL.
        return $url;
    }
}
