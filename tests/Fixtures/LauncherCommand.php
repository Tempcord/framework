<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Enums\EntryPointCommandHandlerType;

/**
 * Discord answers this one itself, so the class carries no method at all.
 */
#[Command(
    name: 'launch',
    description: 'Open the app',
    type: ApplicationCommandTypes::PRIMARY_ENTRY_POINT,
    handler: EntryPointCommandHandlerType::DISCORD_LAUNCH_ACTIVITY,
)]
final class LauncherCommand
{
}
