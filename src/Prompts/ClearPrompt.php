<?php

namespace Tempcord\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempest\Console\Console;

#[Prompt(
    description: 'Clear the console output',
    aliases: ['cls']
)]
final class ClearPrompt
{
    public function __invoke(Console $console): void
    {
        // ANSI escape sequence to clear screen and move cursor to top-left
        $console->writeRaw("\033[2J\033[H");
    }
}
