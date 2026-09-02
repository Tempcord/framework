<?php

namespace Tempcord\Attributes;

use Attribute;

/**
 * Declares an invokable class as work the bot does on a timer.
 *
 * Sweeping rows that have run their course, expiring caches, polling something
 * that has no gateway event — anything that has to happen whether or not
 * anyone is interacting with the bot.
 *
 * The first turn comes after the interval, not at boot: a task is a repeating
 * chore, and something that must happen once at startup belongs in a plugin's
 * boot method where its ordering against everything else is visible.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Scheduled
{
    public function __construct(
        public float $everySeconds,
    ) {}
}
