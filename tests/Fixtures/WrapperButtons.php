<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
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
