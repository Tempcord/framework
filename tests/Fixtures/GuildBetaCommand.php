<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(name: 'beta', description: 'Beta, scoped to the same guild 111', guildId: 111)]
final class GuildBetaCommand
{
    public function __invoke(): void {}
}
