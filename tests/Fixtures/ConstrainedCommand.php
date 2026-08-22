<?php

namespace Tempcord\Tests\Fixtures;

use Ragnarok\Fenrir\Enums\ChannelType;
use Ragnarok\Fenrir\Parts\Channel;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Every option constraint Discord accepts')]
final class ConstrainedCommand
{
    public function __invoke(
        #[Option(description: 'A labelled set', choices: ['Small' => 's', 'Large' => 'l'])]
        string $size,
        #[Option(description: 'A bare list', choices: ['red', 'green'])]
        string $colour,
        #[Option(description: 'Bounded number', minValue: 1, maxValue: 10)]
        int $count,
        #[Option(description: 'Bounded text', minLength: 2, maxLength: 32)]
        string $note,
        #[Option(description: 'Text channels only', channelTypes: [ChannelType::GUILD_TEXT])]
        Channel $channel,
    ): void {}
}
