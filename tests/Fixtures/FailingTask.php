<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Attributes\Scheduled;

#[Scheduled(everySeconds: 0.01)]
final class FailingTask
{
    public function __invoke(): void
    {
        throw new RuntimeException('the database went away');
    }
}
