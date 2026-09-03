<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Button;
use Tempcord\Attributes\ModalSubmit;
use Tempcord\Attributes\SelectMenu;
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;

/**
 * One of each kind of component, guarded, so the shape of interaction a
 * middleware is handed can be checked for all three.
 */
final class GuardedButtons
{
    /** @var list<string> */
    public static array $calls = [];

    #[Button(id: 'guarded.press', middleware: [OuterMiddleware::class, InnerMiddleware::class])]
    public function press(ButtonInteraction $interaction): void
    {
        self::$calls[] = 'press';
    }

    #[Button(id: 'guarded.refused', middleware: [RefusingMiddleware::class])]
    public function refused(ButtonInteraction $interaction): void
    {
        self::$calls[] = 'refused';
    }

    #[Button(id: 'guarded.open')]
    public function open(ButtonInteraction $interaction): void
    {
        self::$calls[] = 'open';
    }

    /**
     * Takes no interaction at all, so a refusal has to answer with one the
     * dispatcher built rather than one the handler asked for.
     */
    #[Button(id: 'guarded.blind.{team}', middleware: [RecordingInteractionMiddleware::class])]
    public function blind(string $team): void
    {
        self::$calls[] = 'blind:' . $team;
    }

    #[SelectMenu(id: 'guarded.pick', middleware: [RecordingInteractionMiddleware::class])]
    public function pick(ComponentInteraction $interaction): void
    {
        self::$calls[] = 'pick';
    }

    #[ModalSubmit(id: 'guarded.submit', middleware: [RecordingInteractionMiddleware::class])]
    public function submit(ModalSubmitInteraction $interaction): void
    {
        self::$calls[] = 'submit';
    }
}
