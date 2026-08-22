<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;

#[Command(description: 'Moderation tools')]
final class ModerationCommand
{
    #[Subcommand(name: 'kick', description: 'Kick a member')]
    public function kick(
        #[Option(description: 'Reason for the kick')] string $reason,
    ): string {
        return 'kicked: ' . $reason;
    }
}
