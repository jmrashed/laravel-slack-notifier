<?php

return [
    /*
     * Slack incoming webhook URLs.
     * These URLs are used to send messages to Slack. Define your webhook URLs here.
     */
    'webhook_urls' => [
        'default' => env('LOG_SLACK_WEBHOOK_URL'),
    ],

    /*
     * Override the Slack channel to which the message will be sent.
     * If you want to specify a different channel, set the value here.
     */
    'channel' => env('LOG_SLACK_CHANNEL'),

    /*
     * The name of the Slack bot that will send messages.
     * This name will appear as the sender in Slack.
     */
    'username' => env('APP_NAME', 'Laravel Log'),

    /*
     * The emoji that will represent the Slack bot.
     * Default is ':boom:', but you can customize it here.
     */
    'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),

    /*
     * When an exception occurs repeatedly, the cache prevents repeated notifications.
     * This defines the number of seconds for which the same exception will be suppressed.
     * Set to 0 for no caching.
     */
    'cache_seconds' => env('LOG_SLACK_CACHE_SECONDS', 0),

    /*
     * The formatter class to be used for formatting the Slack message.
     * You can create your own formatter class if needed.
     */
    'formatter' => Stasadev\SlackNotifier\SlackNotifierFormatter::class,

    /*
     * The context that will be included in the Slack message.
     * Available values: 'get', 'post', 'request', 'headers', 'files', 'cookie', 'session', 'server'.
     */
    'context' => [
        'get',
        'post',
        'cookie',
        'session',
    ],

    /*
     * A list of context variables that will not be sent to Slack.
     * These are typically sensitive data that you don’t want to share in the logs.
     */
    'dont_flash' => [
        'current_password',
        'password',
        'password_confirmation',
    ],

    /*
     * List of patterns to exclude lines from the stack trace.
     * These are typically vendor files that are not useful for debugging.
     */
    'dont_trace' => [
        '/vendor/symfony/',
        '/vendor/laravel/framework/',
        '/vendor/barryvdh/laravel-debugbar/',
    ],
];
