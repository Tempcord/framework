<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Interfaces\Middleware;

/**
 * Writes its own name into a shared trail on the way in, so a test can read
 * back the order the chain actually ran in.
 */
class TrailMiddleware implements Middleware
{
    /** @var list<string> */
    public static array $trail = [];

    public function __construct(
        public string $label = 'anonymous',
    ) {}

    public function __invoke(CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction, callable $next): void
    {
        self::$trail[] = $this->label;

        $next($interaction);
    }
}
