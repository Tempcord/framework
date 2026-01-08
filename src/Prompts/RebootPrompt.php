<?php

namespace Tempcord\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempcord\Attributes\Prompts\PromptOption;
use Tempcord\Tempcord;
use Tempest\Console\Console;

#[Prompt(
    description: 'Restart the Discord bot connection'
)]
final class RebootPrompt
{
    public function __construct(
        private readonly Tempcord $tempcord,
    )
    {
    }

    public function __invoke(
        Console $console,
        #[PromptOption(
            description: 'Reboot mode (soft or hard)',
            autocomplete: ['soft', 'hard']
        )]
        string $mode = 'soft'
    ): void {
        if (!in_array($mode, ['soft', 'hard'], true)) {
            $console->error("Invalid mode: {$mode}. Use 'soft' or 'hard'.");
            return;
        }

        $console->writeln("Rebooting ({$mode} mode)...");

        if ($mode === 'soft') {
            // Soft reboot: reconnect gateway
            try {
                $this->tempcord->reconnect();
                $console->writeln('Gateway reconnected');
            } catch (\Throwable $e) {
                $console->error('Failed to reconnect: ' . $e->getMessage());
            }
        } else {
            // Hard reboot: full process restart (requires pcntl extension)
            if (!function_exists('pcntl_exec')) {
                $console->writeln('Hard reboot requires pcntl extension. Performing soft reboot instead.');
                $this->tempcord->reconnect();
                return;
            }

            $console->writeln('Hard reboot not yet implemented. Use soft mode for now.');
            // Future: could use pcntl_exec to restart the entire PHP process
        }
    }
}
