<?php

namespace Tempcord\Tests\Fixtures;

use CyberWolf\Discord\Interaction\ButtonInteraction;
use Tempcord\Attributes\Button;

#[Button]
final class ReportButton
{
    /** @var list<ButtonInteraction> */
    public static array $presses = [];

    public function __invoke(ButtonInteraction $interaction): void
    {
        self::$presses[] = $interaction;
    }
}
