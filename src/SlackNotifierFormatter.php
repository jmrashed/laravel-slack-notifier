<?php

namespace Jmrashed\SlackNotifier;

use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Arr;
use Monolog\Formatter\NormalizerFormatter;
use ReflectionMethod;
use Jmrashed\SlackNotifier\Notifications\SendToSlack;
use Throwable;

class SlackNotifierFormatter
{
    /**
     * @var SlackMessage
     */
    protected $message;

    /**
     * @var NormalizerFormatter
     */
    protected $normalizer;

    /**
     * @var string[]
     */
    protected $context;

    /**
     * @var string[]
     */
    protected $dontFlash;

    /**
     * @var string[]
     */
    protected $dontTrace;

    /**
     * Constructor to initialize the Slack message and other configurations
     */
    public function __construct()
    {
        $this->message = new SlackMessage();
        $this->normalizer = new NormalizerFormatter();

        // Fetch configuration values from the config file
        $this->context = config('slack-notifier.context', [
            'get', 'post', 'cookie', 'session',
        ]);

        $this->dontFlash = config('slack-notifier.dont_flash', [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $this->dontTrace = config('slack-notifier.dont_trace', [
            '/vendor/symfony/',
            '/vendor/laravel/framework/',
            '/vendor/barryvdh/laravel-debugbar/',
        ]);
    }

    /**
     * Format the Slack message for a given notification.
     *
     * @param SendToSlack $notification
     * @return SlackMessage
     */
    public function format(SendToSlack $notification): SlackMessage
    {
        // Determine if the notification contains an exception or variable and format accordingly
        if ($exception = $notification->getException()) {
            $slackMessage = $this->formatException($exception);
        } else {
            $slackMessage = $this->formatVariable($notification->getVariable());
        }

        // Add context to the Slack message attachment
        $slackMessage->attachment(function (SlackAttachment $attachment) {
            if (! $context = $this->getContext()) {
                return;
            }

            $attachment->pretext('Context')
                ->content('```'.$context.'```')
                ->color('#3498DB')
                ->markdown(['text']);
        });

        return $slackMessage;
    }

    /**
     * Format the exception details into a Slack message.
     *
     * @param Throwable $exception
     * @param SlackMessage|null $slackMessage
     * @return SlackMessage
     */
    protected function formatException(Throwable $exception, ?SlackMessage $slackMessage = null): SlackMessage
    {
        // Get pretext for the exception message
        $pretext = $this->getPretext($exception);

        // If there is a previous exception, update the pretext
        if ($slackMessage) {
            $pretext = 'Previous exception';
        }

        // Format the exception error message
        $this->message->error();

        $this->message->attachment(function (SlackAttachment $attachment) use ($exception, $pretext) {
            // Normalize and format exception details
            $content = $this->normalize(get_class($exception).': '.$exception->getMessage().' in '.$exception->getFile().':'.$exception->getLine());

            $attachment->pretext($pretext)
                ->content('```'.$content.'```')
                ->fallback(config('app.name').': '.$content)
                ->markdown(['text']);
        });

        // Add stack trace to the attachment
        $this->message->attachment(function (SlackAttachment $attachment) use ($exception) {
            $attachment->pretext('Stack trace')
                ->content('```'.$this->normalizeTrace($exception).'```');
        });

        // If there is a previous exception, recurse to format it as well
        if ($previous = $exception->getPrevious()) {
            return $this->formatException($previous, $this->message);
        }

        return $this->message;
    }

    /**
     * Format a variable into a Slack message.
     *
     * @param mixed $variable
     * @return SlackMessage
     */
    protected function formatVariable($variable): SlackMessage
    {
        $this->message->success();

        // Normalize and convert variable into string
        $variable = $this->normalizeToString($variable);

        // Add variable details to the Slack attachment
        $this->message->attachment(function (SlackAttachment $attachment) use ($variable) {
            $attachment->pretext($this->getPretext($variable))
                ->content('```'.$variable.'```')
                ->fallback(config('app.name').': '.$variable)
                ->markdown(['text']);
        });

        return $this->message;
    }

    /**
     * Get the pretext for the Slack message based on the source of the variable.
     *
     * @param mixed $variable
     * @return string
     */
    protected function getPretext($variable): string
    {
        $source = app()->runningInConsole() ? 'console' : request()->url();

        if ($variable instanceof Throwable) {
            return 'Caught an exception from '.$source;
        }

        return 'Received value from '.$source;
    }

    /**
     * Normalize the given data for proper formatting.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function normalize($data)
    {
        // Use the normalizer method to normalize the data
        if (method_exists($this->normalizer, 'normalizeValue')) {
            return $this->normalizer->normalizeValue($data);
        }

        // Use Reflection if method does not exist
        $r = new ReflectionMethod($this->normalizer, 'normalize');
        $r->setAccessible(true);

        return $r->invoke($this->normalizer, $data);
    }

    /**
     * Convert a variable to a string for Slack message formatting.
     *
     * @param mixed $variable
     * @return string
     */
    protected function normalizeToString($variable): string
    {
        $variable = $this->normalize($variable);

        try {
            if (is_null($variable)) {
                return 'null';
            } elseif (is_bool($variable)) {
                return $variable ? 'true' : 'false';
            } elseif (is_array($variable)) {
                return print_r($variable, true);
            } elseif (is_object($variable)) {
                return json_encode($variable);
            } else {
                return (string) $variable;
            }
        } catch (Throwable $e) {
            return 'Failed to normalize variable.';
        }
    }

    /**
     * Normalize the stack trace of an exception, excluding certain patterns.
     *
     * @param Throwable $exception
     * @return string
     */
    protected function normalizeTrace(Throwable $exception): string
    {
        $emptyLineCharacter = '   ...';
        $lines = explode("\n", $exception->getTraceAsString());
        $filteredLines = [];

        foreach ($lines as $line) {
            $shouldExclude = false;

            // Check if the line should be excluded based on patterns
            foreach ($this->dontTrace as $excludePattern) {
                if (str_starts_with($line, '#') && str_contains($line, $excludePattern)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude && end($filteredLines) !== $emptyLineCharacter) {
                $filteredLines[] = $emptyLineCharacter;
            } elseif (! $shouldExclude) {
                $filteredLines[] = $line;
            }
        }

        return implode("\n", $filteredLines);
    }

    /**
     * Get the context data (such as request data) for the Slack message.
     *
     * @return string|null
     */
    protected function getContext(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $context = [];

        foreach ($this->context as $item) {
            $value = null;
            $format = '$_%s = %s';

            // Fetch specific request data based on configuration
            if ($item === 'get') {
                $value = request()->query();
            } elseif ($item === 'post') {
                $value = request()->post();
            } elseif ($item === 'request') {
                $value = request()->all();
            } elseif ($item === 'headers') {
                $value = request()->headers->all();
            } elseif ($item === 'files') {
                $value = request()->allFiles();
            } elseif ($item === 'cookie') {
                $value = request()->cookie();
            } elseif ($item === 'session' && request()->hasSession()) {
                $value = request()->session()->all();
            } elseif ($item === 'server') {
                $value = request()->server();
            }

            // Exclude sensitive data from the context
            if (is_array($value) && ($value = Arr::except($value, $this->dontFlash))) {
                $context[] = sprintf(
                    $format,
                    strtoupper($item),
                    print_r($this->normalize($value), true)
                );
            }
        }

        return implode("\n", $context);
    }
}
