<?php

namespace Tempcord\Prompts;

use Ragnarok\Fenrir\Discord;
use Tempcord\Attributes\Prompts\Prompt;
use Tempcord\Tempcord;
use Tempest\Console\Console;
use function Tempest\get;

#[Prompt(
    description: 'Show bot connection status and statistics'
)]
final class StatusPrompt
{
    public function __construct(
        private readonly Tempcord $tempcord,
    )
    {
    }

    public function __invoke(
        Console $console,
        #[\Tempcord\Attributes\Prompts\PromptOption(description: 'Show detailed information', isFlag: true)]
        bool $verbose = false,
        #[\Tempcord\Attributes\Prompts\PromptOption(description: 'Output format (text, json)', autocomplete: ['text', 'json'])]
        string $format = 'text',
    ): void
    {
        try {
            $discord = get(Discord::class);

            // Collect status data
            $commandCount = count($this->tempcord->commandsRegistry->bucket->items);
            $componentCount = count($this->tempcord->componentsRegistry->bucket->items);
            $taskCount = count($this->tempcord->tasksRegistry->bucket->items);
            $pluginCount = count($this->tempcord->pluginRegistry->getPlugins());

            $data = [
                'gateway' => 'Connected',
                'commands' => $commandCount,
                'components' => $componentCount,
                'tasks' => $taskCount,
                'plugins' => $pluginCount,
            ];

            // Output based on format
            if ($format === 'json') {
                $console->writeln(json_encode($data, JSON_PRETTY_PRINT));
                return;
            }

            // Text format
            $console->writeln('');
            $console->header('Bot Status');
            $console->writeln('');

            $console->writeln(
                "  <style='fg-gray'>Gateway:</style> <style='fg-green'>{$data['gateway']}</style>"
            );
            $console->writeln(
                "  <style='fg-gray'>Commands:</style> <style='fg-cyan'>{$data['commands']}</style>"
            );
            $console->writeln(
                "  <style='fg-gray'>Components:</style> <style='fg-cyan'>{$data['components']}</style>"
            );
            $console->writeln(
                "  <style='fg-gray'>Tasks:</style> <style='fg-cyan'>{$data['tasks']}</style>"
            );
            $console->writeln(
                "  <style='fg-gray'>Plugins:</style> <style='fg-cyan'>{$data['plugins']}</style>"
            );

            if ($verbose) {
                $console->writeln('');
                $console->writeln('  <style="fg-gray">Detailed Information:</style>');
                $console->writeln('  <style="fg-gray">Memory Usage:</style> <style="fg-cyan">' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB</style>');
                $console->writeln('  <style="fg-gray">Peak Memory:</style> <style="fg-cyan">' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB</style>');
            }

            $console->writeln('');
        } catch (\Throwable $e) {
            $console->writeln('');
            $console->writeln('<style="bg-dark-red fg-white"> Error </style>');
            $console->error('Failed to retrieve status: ' . $e->getMessage());
        }
    }
}
