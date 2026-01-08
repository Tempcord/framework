<?php

namespace Tempcord\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempcord\Support\Prompts\PromptsBucket;
use Tempest\Console\Console;

#[Prompt(
    description: 'Show available prompts and their descriptions',
    aliases: ['?']
)]
final class HelpPrompt
{
    public function __construct(
        private readonly PromptsBucket $bucket,
    )
    {
    }

    public function __invoke(Console $console): void
    {
        $console->writeln('');
        $console->header('Available Prompts');
        $console->writeln('');

        // Get unique prompts (exclude aliases)
        $seen = [];
        $prompts = [];

        foreach ($this->bucket->items as $name => $prompt) {
            if (!in_array($prompt->name, $seen, true)) {
                $prompts[$prompt->name] = $prompt;
                $seen[] = $prompt->name;
            }
        }

        // Sort by name
        ksort($prompts);

        foreach ($prompts as $prompt) {
            $aliases = empty($prompt->aliases)
                ? ''
                : ' <style="fg-gray">(aliases: ' . implode(', ', $prompt->aliases) . ')</style>';

            $console->writeln(
                "  <style='fg-cyan'>/{$prompt->name}</style>{$aliases}"
            );

            if ($prompt->description) {
                $console->writeln(
                    "    <style='fg-gray'>{$prompt->description}</style>"
                );
            }

            // Show options if any
            if (!empty($prompt->options)) {
                $console->writeln("    <style='fg-gray'>Options:</style>");
                foreach ($prompt->options as $option) {
                    $required = $option->isRequired ? '<style="fg-red">*</style>' : '';
                    $console->writeln(
                        "      <style='fg-yellow'>--{$option->name}</style>{$required} - {$option->description}"
                    );
                }
            }

            $console->writeln('');
        }

        $console->writeln("<style='fg-gray'>Type <style='fg-cyan'>exit</style> or <style='fg-cyan'>quit</style> to shutdown the bot.</style>");
    }
}
