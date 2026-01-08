<?php

namespace Tempcord\Support\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempest\Console\Console;
use Tempest\Container\Singleton;
use Tempest\Log\Logger;
use Tempest\Support\Arr\ImmutableArray;
use Throwable;
use function Tempest\get;

#[Singleton]
class PromptsBucket
{
    /** @var ImmutableArray<string, Prompt> */
    protected(set) ImmutableArray $items;

    public function __construct()
    {
        $this->items = new ImmutableArray();
    }

    public function add(Prompt $prompt): void
    {
        $this->items = $this->items->put($prompt->name, $prompt);

        // Also register aliases
        foreach ($prompt->aliases as $alias) {
            $this->items = $this->items->put($alias, $prompt);
        }
    }

    public function handle(string $input, Console $console): void
    {
        $logger = get(Logger::class);

        try {
            $parsed = PromptParser::parse($input);

            /** @var Prompt|null $prompt */
            $prompt = $this->items->get($parsed->name);

            if ($prompt === null) {
                $this->handleUnknownPrompt($parsed->name, $console);
                return;
            }

            $prompt->handler->handle($parsed->arguments, $console);
        } catch (Throwable $e) {
            $logger->error("Prompt failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            $console->writeln('');
            $console->writeln('<style="bg-dark-red fg-white"> Error </style>');
            $console->error("Error: {$e->getMessage()}");
        }
    }

    private function handleUnknownPrompt(string $name, Console $console): void
    {
        $console->writeln('');
        $console->writeln('<style="bg-dark-red fg-white"> Error </style>');
        $console->writeln("<style='fg-red'>Prompt <em>{$name}</em> not found.</style>");

        $similarPrompts = $this->getSimilarPrompts($name);

        if ($similarPrompts->isEmpty()) {
            $console->writeln("Type <style='fg-cyan'>/help</style> or <style='fg-cyan'>help</style> to see available prompts.");
            return;
        }

        if ($similarPrompts->count() === 1) {
            $matchedPrompt = $similarPrompts->first();

            if ($console->confirm("Did you mean <em>{$matchedPrompt}</em>?", default: true)) {
                $this->handle($matchedPrompt, $console);
                return;
            }

            return;
        }

        // Multiple matches - use interactive search
        try {
            $intendedPrompt = $console->ask(
                'Did you mean to run one of these?',
                options: $similarPrompts->toArray(),
            );

            if ($intendedPrompt !== null) {
                $this->handle($intendedPrompt, $console);
            }
        } catch (Throwable $e) {
            // User cancelled or error occurred
            $console->writeln("Type <style='fg-cyan'>/help</style> to see available prompts.");
        }
    }

    private function getSimilarPrompts(string $search): ImmutableArray
    {
        /** @var ImmutableArray<array-key, string> $suggestions */
        $suggestions = new ImmutableArray();

        // Get unique prompt names (exclude aliases)
        $seen = [];
        $uniquePrompts = [];

        foreach ($this->items as $name => $prompt) {
            if (!in_array($prompt->name, $seen, true)) {
                $uniquePrompts[] = $prompt->name;
                $seen[] = $prompt->name;
            }
        }

        foreach ($uniquePrompts as $promptName) {
            // Exact prefix match
            if (str_starts_with($promptName, $search)) {
                $suggestions = $suggestions->put($promptName, $promptName);
                continue;
            }

            // Case-insensitive match
            if (str_starts_with(strtolower($promptName), strtolower($search))) {
                $suggestions = $suggestions->put($promptName, $promptName);
                continue;
            }

            // Levenshtein distance (fuzzy matching)
            if (levenshtein($promptName, $search) <= 2) {
                $suggestions = $suggestions->put($promptName, $promptName);
                continue;
            }
        }

        // Sort by levenshtein distance
        $sorted = [];
        foreach ($suggestions as $suggestion) {
            $sorted[] = [
                'levenshtein' => levenshtein($suggestion, $search),
                'suggestion' => $suggestion,
            ];
        }

        usort($sorted, fn($a, $b) => $a['levenshtein'] <=> $b['levenshtein']);

        return new ImmutableArray(array_map(fn($item) => $item['suggestion'], $sorted));
    }
}
