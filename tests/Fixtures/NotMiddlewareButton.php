<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Component\Button;
use Tempcord\Discord\Interaction\ButtonInteraction;

#[Button(id: 'not.middleware', middleware: [TrackRepository::class])]
final class NotMiddlewareButton
{
    public function __invoke(ButtonInteraction $interaction): void {}
}
