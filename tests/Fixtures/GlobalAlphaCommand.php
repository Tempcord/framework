<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(name: 'alpha', description: 'Alpha, registered globally')]
final class GlobalAlphaCommand
{
    public function __invoke(): void {}
}
