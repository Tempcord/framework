<?php

namespace Tempcord\Tests\Fixtures;

/**
 * The simplest command there is: invokable, no options at all.
 */

use Tempcord\Attributes\Command;

#[Command(description: 'Takes no options whatsoever')]
final class BareCommand
{
    public function __invoke(): string
    {
        return 'bare';
    }
}
