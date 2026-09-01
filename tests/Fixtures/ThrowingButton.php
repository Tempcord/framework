<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Attributes\Button;

#[Button]
final class ThrowingButton
{
    public function __invoke(): void
    {
        throw new RuntimeException('nope');
    }
}
