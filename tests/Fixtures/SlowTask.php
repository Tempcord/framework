<?php

namespace Tempcord\Tests\Fixtures;

use React\Promise\Deferred;
use Tempcord\Attributes\Scheduled;

use function React\Async\await;

/**
 * A task that outlasts its own interval, held open by the test until it is
 * ready to let it finish.
 */
#[Scheduled(everySeconds: 0.01)]
final class SlowTask
{
    public static int $started = 0;

    public static ?Deferred $holding = null;

    public function __invoke(): void
    {
        self::$started++;

        await(self::$holding->promise());
    }
}
