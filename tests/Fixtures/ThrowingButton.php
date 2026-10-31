<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Declare\Component\Button;

#[Button]
final class ThrowingButton
{
    public function __invoke(): void
    {
        throw new RuntimeException('nope');
    }
}
