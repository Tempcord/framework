<?php

namespace Tempcord\Runtime;

use Tempest\Console\Console;

/**
 * Renders outcomes to the console.
 */
final readonly class OutcomeReporter
{
    public function __construct(
        private Console $console,
    ) {}

    /**
     * @param iterable<Outcome> $outcomes
     */
    public function report(iterable $outcomes): void
    {
        foreach ($outcomes as $outcome) {
            match ($outcome->level) {
                OutcomeLevel::Success => $this->console->success($outcome->message),
                OutcomeLevel::Warning => $this->console->warning($outcome->message),
                OutcomeLevel::Error => $this->console->error($outcome->message),
            };
        }
    }
}
