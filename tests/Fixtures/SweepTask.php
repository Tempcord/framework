<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Scheduled;

/**
 * The ordinary case: a chore that runs often and finishes quickly.
 */
#[Scheduled(everySeconds: 0.01)]
final class SweepTask
{
    public static int $turns = 0;

    public function __invoke(): void
    {
        self::$turns++;
    }
}
