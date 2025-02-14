<?php

namespace Jmrashed\SlackNotifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * SlackNotifier facade for sending messages to Slack.
 *
 * @method static self to(string $to)
 * @method static self channel(?string $channel)
 * @method static self username(string $username)
 * @method static self emoji(string $emoji)
 * @method static self cacheSeconds(int $cacheSeconds)
 * @method static void send($message)
 *
 * @see \Jmrashed\SlackNotifier\Notifications\SendToSlack
 */
class SlackNotifier extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Jmrashed\SlackNotifier\Notifications\SendToSlack::class;
    }
}
