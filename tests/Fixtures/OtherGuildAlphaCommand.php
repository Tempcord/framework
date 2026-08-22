<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(name: 'alpha', description: 'Alpha, scoped to a different guild', guildId: 222)]
final class OtherGuildAlphaCommand
{
    public function __invoke(): void {}
}
