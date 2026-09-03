<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Button;
use Tempcord\Discord\Interaction\ButtonInteraction;

#[Button(id: 'not.middleware', middleware: [TrackRepository::class])]
final class NotMiddlewareButton
{
    public function __invoke(ButtonInteraction $interaction): void {}
}
