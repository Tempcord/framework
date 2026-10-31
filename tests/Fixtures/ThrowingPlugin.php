<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Declare\Contracts\Plugin;
use Tempcord\Tempcord;

final class ThrowingPlugin implements Plugin
{
    public function boot(Tempcord $tempcord): void
    {
        throw new RuntimeException('could not reach the scheduler');
    }
}
