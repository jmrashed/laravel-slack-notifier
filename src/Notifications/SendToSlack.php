<?php

namespace Jmrashed\SlackNotifier\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Jmrashed\SlackNotifier\Config;
use Jmrashed\SlackNotifier\Exceptions\WebhookSendFail;
use Jmrashed\SlackNotifier\SlackNotifierFormatter;
use Throwable;

class SendToSlack extends Notification
{
    use Queueable;

    /**
     * @var Throwable|null The exception to send (if any).
     */
    protected $exception;

    /**
     * @var mixed The variable to send (if no exception).
     */
    protected $variable;

    /**
     * @var string The webhook URL to send the message to.
     */
    protected $to;

    /**
     * @var null|string The Slack channel to send the message to (optional).
     */
    protected $channel;

    /**
     * @var string The username for the Slack notification.
     */
    protected $username;

    /**
     * @var string The emoji to use for the Slack notification.
     */
    protected $emoji;

    /**
     * @var int The number of seconds to cache the notification (if needed).
     */
    protected $cacheSeconds;

    /**
     * @var SlackNotifierFormatter The formatter for Slack messages.
     */
    protected $formatter;

    /**
     * SendToSlack constructor.
     */
    public function __construct()
    {
        // Set default values for the notification properties.
        $this->to('default')
            ->channel(config('slack-notifier.channel', ''))
            ->username(config('slack-notifier.username', 'Laravel Log'))
            ->emoji(config('slack-notifier.emoji', ':boom:'))
            ->cacheSeconds((int) config('slack-notifier.cache_seconds', 0));

        // Get the formatter class from the config.
        $this->formatter = Config::getFormatter();
    }

    /**
     * Set the webhook URL to send the notification to.
     *
     * @param string $to The webhook URL (or name from config).
     * @return $this
     */
    public function to(string $to): self
    {
        $this->to = Config::getWebhookUrl($to);

        return $this;
    }

    /**
     * Set the Slack channel to send the message to.
     *
     * @param string|null $channel The channel name.
     * @return $this
     */
    public function channel(?string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Set the username to be displayed in the Slack message.
     *
     * @param string $username The username.
     * @return $this
     */
    public function username(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the emoji to be displayed in the Slack message.
     *
     * @param string $emoji The emoji.
     * @return $this
     */
    public function emoji(string $emoji): self
    {
        $this->emoji = $emoji;

        return $this;
    }

    /**
     * Set the cache duration in seconds.
     *
     * @param int $cacheSeconds Cache duration in seconds.
     * @return $this
     */
    public function cacheSeconds(int $cacheSeconds): self
    {
        $this->cacheSeconds = $cacheSeconds;

        return $this;
    }

    /**
     * Send the notification message to Slack.
     *
     * @param mixed $message The message or exception to send.
     * @return void
     */
    public function send($message): void
    {
        // Determine if the message is an exception or a regular variable.
        if ($message instanceof Throwable) {
            $this->exception = $message;
        } else {
            $this->variable = $message;
        }

        try {
            // Skip sending if the exception is related to Webhook sending failure.
            if ($this->exception instanceof WebhookSendFail) {
                return;
            }

            // Use Laravel's Notification facade to send the notification to Slack.
            NotificationFacade::route('slack', $this->to)->notify($this);
        } catch (Throwable $e) {
            // Log any errors encountered when trying to send the notification.
            report(WebhookSendFail::make($e));
        }
    }

    /**
     * Determine the channels or methods the notification should be sent through.
     *
     * @param mixed $notifiable The entity being notified.
     * @return array The notification channels.
     */
    public function via($notifiable): array
    {
        return $this->cached() ? [] : ['slack'];
    }

    /**
     * Prepare the Slack message for sending.
     *
     * @param mixed $notifiable The entity being notified.
     * @return SlackMessage The formatted Slack message.
     */
    public function toSlack($notifiable): SlackMessage
    {
        $slackMessage = $this->formatter->format($this);

        return $slackMessage->from($this->username, $this->emoji)
            ->to($this->channel);
    }

    /**
     * Get the exception if available.
     *
     * @return Throwable|null The exception instance, if any.
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Get the variable (non-exception data) if available.
     *
     * @return mixed The variable, if any.
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Check if the notification should be cached.
     *
     * @return bool True if caching is enabled, false otherwise.
     */
    protected function cached(): bool
    {
        // Do not cache unless there's an exception to report.
        if (! $this->exception) {
            return false;
        }

        // If caching is enabled, check if the exception is already cached.
        $seconds = $this->cacheSeconds;

        if ($seconds < 1) {
            return false;
        }

        // Generate a unique cache key for this exception.
        $key = Str::kebab($this->username.' Slack Log Message')
            .'-'.sha1($this->exception);

        // Return true if the exception has already been cached.
        if (cache()->get($key)) {
            return true;
        }

        // Otherwise, cache the exception for the specified duration.
        cache()->set($key, true, $seconds);

        return false;
    }
}
