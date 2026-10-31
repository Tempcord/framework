<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;
use Tempcord\Declare\Command\Subcommand;

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
