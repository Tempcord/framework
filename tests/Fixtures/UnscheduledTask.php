<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Scheduled;

/**
 * An interval of zero asks the loop to run this as fast as it can, starving
 * everything else including the gateway heartbeat.
 */
#[Scheduled(everySeconds: 0)]
final class UnscheduledTask
{
    public function __invoke(): void {}
}
