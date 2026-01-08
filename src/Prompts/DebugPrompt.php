<?php

namespace Tempcord\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempcord\Support\Prompts\PromptsBucket;
use Tempest\Console\Console;

#[Prompt(description: 'Debug prompt discovery')]
final class DebugPrompt
{
    public function __construct(
        private readonly PromptsBucket $bucket,
    )
    {
    }

    public function __invoke(Console $console): void
    {
        $console->header('Debug Info');
        $console->writeln('');

        $count = count($this->bucket->items);
        $console->writeln("Prompts in bucket: <style='fg-cyan'>{$count}</style>");
        $console->writeln('');

        if ($count === 0) {
            $console->error('No prompts discovered!');
            $console->writeln('This means PromptsDiscovery is not running.');
        } else {
            $console->writeln('Registered prompts:');
            foreach ($this->bucket->items as $name => $prompt) {
                $console->writeln("  - {$name}");
            }
        }
    }
}
