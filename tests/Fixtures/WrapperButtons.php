<?php

namespace Tempcord\Tests\Fixtures;

use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use CyberWolf\Discord\Interaction\ButtonInteraction;
use CyberWolf\Discord\Interaction\ComponentInteraction;
use CyberWolf\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Attributes\Button;
use Tempcord\Attributes\ModalSubmit;

/**
 * Every shape a handler may ask for the interaction in.
 */
final class WrapperButtons
{
    #[Button(id: 'raw')]
    public function raw(InteractionCreate $interaction): void {}

    #[Button(id: 'wrapped')]
    public function wrapped(ButtonInteraction $interaction): void {}

    #[Button(id: 'component')]
    public function component(ComponentInteraction $interaction): void {}

    #[ModalSubmit(id: 'submitted')]
    public function submitted(ModalSubmitInteraction $interaction): void {}

    #[Button(id: 'typed.{count}.{ratio}.{flag}')]
    public function typed(int $count, float $ratio, bool $flag): void {}

    #[Button(id: 'severity.{level}')]
    public function severity(Severity $level): void {}

    #[Button(id: 'required.{given}')]
    public function required(string $given, string $missing): void {}
}
