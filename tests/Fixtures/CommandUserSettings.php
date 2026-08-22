<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(description: 'Prefix and suffix are both stripped')]
final class CommandUserSettings
{
    public function __invoke(): void {}
}
