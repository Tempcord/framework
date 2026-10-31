<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;

#[Command(name: 'alpha', description: 'Alpha, registered globally')]
final class GlobalAlphaCommand
{
    public function __invoke(): void {}
}
