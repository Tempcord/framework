<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Declare\Command\Command;

#[Command(description: 'Always fails')]
final class ThrowingCommand
{
    public function __invoke(): void
    {
        throw new RuntimeException('nope');
    }
}
