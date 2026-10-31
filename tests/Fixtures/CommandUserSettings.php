<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;

#[Command(description: 'Prefix and suffix are both stripped')]
final class CommandUserSettings
{
    public function __invoke(): void {}
}
