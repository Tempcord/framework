<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(name: 'alpha', description: 'Alpha, scoped to guild 111', guildId: 111)]
final class GuildAlphaCommand
{
    public function __invoke(): void {}
}
