<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;

#[Command(description: 'Wants the gateway event as well as the interaction')]
final class GatewayEventCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        InteractionCreate $event,
        #[Option(description: 'Something to pass along')]
        ?string $note = null,
    ): void {}
}
