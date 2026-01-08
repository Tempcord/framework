<?php

namespace Tempcord\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempcord\Tempcord;
use Tempest\Console\Console;

#[Prompt(
    description: 'Gracefully shutdown the bot',
    aliases: ['quit']
)]
final class ExitPrompt
{
    public function __construct(
        private readonly Tempcord $tempcord,
    )
    {
    }

    public function __invoke(Console $console): void
    {
        $console->writeln('');
        $console->writeln('Shutting down...');
        $this->tempcord->shutdown();
        exit(0);
    }
}
