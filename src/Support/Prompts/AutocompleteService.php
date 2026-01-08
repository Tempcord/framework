<?php

namespace Tempcord\Support\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempest\Container\Singleton;
use Tempest\Support\Arr\ImmutableArray;

#[Singleton]
final class AutocompleteService
{
    public function __construct(
        private readonly PromptsBucket $bucket,
    )
    {
    }

    /**
     * Get autocomplete suggestions for the current input.
     *
     * @param string $input Current input line
     * @return AutocompleteResult
     */
    public function getCompletions(string $input): AutocompleteResult
    {
        $parsed = $this->parseInput($input);

        // If we're typing the command name, suggest prompts
        if ($parsed['context'] === 'command') {
            return $this->getPromptCompletions($parsed['partial']);
        }

        // If we're typing an option, suggest options for this prompt
        if ($parsed['context'] === 'option') {
            return $this->getOptionCompletions($parsed['command'], $parsed['partial']);
        }

        // If we're typing a value, suggest values for this option
        if ($parsed['context'] === 'value' && $parsed['option'] !== null) {
            return $this->getValueCompletions($parsed['command'], $parsed['option'], $parsed['partial']);
        }

        return new AutocompleteResult([]);
    }

    /**
     * Parse input to understand what we're completing.
     *
     * @param string $input
     * @return array{context: string, command: string|null, partial: string, option: string|null, prefix: string}
     */
    private function parseInput(string $input): array
    {
        $trimmed = trim($input);
        $hasTrailingSpace = str_ends_with($input, ' ');

        // Empty input - completing command
        if ($trimmed === '') {
            return [
                'context' => 'command',
                'command' => null,
                'partial' => '',
                'option' => null,
                'prefix' => '',
            ];
        }

        // Parse the input
        $parts = preg_split('/\s+/', $trimmed);

        // If we have only one part and no trailing space, we're still typing the command
        if (count($parts) === 1 && !$hasTrailingSpace) {
            return [
                'context' => 'command',
                'command' => null,
                'partial' => $parts[0],
                'option' => null,
                'prefix' => '',
            ];
        }

        // First part is the command, rest are arguments
        $command = $parts[0];
        $lastPart = end($parts);

        // If the last part starts with --, we're completing an option name
        if (str_starts_with($lastPart, '--') && !$hasTrailingSpace) {
            // Prefix is everything before the current incomplete option
            $prefix = implode(' ', array_slice($parts, 0, -1));
            if ($prefix) {
                $prefix .= ' ';
            }

            return [
                'context' => 'option',
                'command' => $command,
                'partial' => substr($lastPart, 2), // Remove --
                'option' => null,
                'prefix' => $prefix,
            ];
        }

        // Check if previous part was an option name (completing value)
        if (count($parts) >= 2 && !$hasTrailingSpace) {
            $prevPart = $parts[count($parts) - 2];
            if (str_starts_with($prevPart, '--') && strpos($prevPart, '=') === false) {
                $optionName = substr($prevPart, 2);
                // Prefix includes everything up to the option being completed
                $prefix = implode(' ', array_slice($parts, 0, -1)) . ' ';

                return [
                    'context' => 'value',
                    'command' => $command,
                    'partial' => $lastPart,
                    'option' => $optionName,
                    'prefix' => $prefix,
                ];
            }
        }

        // If input ends with space or last part is complete option, show more options
        if ($hasTrailingSpace || (str_starts_with($lastPart, '--') && strpos($lastPart, '=') !== false)) {
            return [
                'context' => 'option',
                'command' => $command,
                'partial' => '',
                'option' => null,
                'prefix' => $trimmed . ' ',
            ];
        }

        // Otherwise, we're typing a value after an option
        return [
            'context' => 'value',
            'command' => $command,
            'partial' => $lastPart,
            'option' => null,
            'prefix' => $trimmed,
        ];
    }

    /**
     * Get prompt name completions.
     */
    private function getPromptCompletions(string $partial): AutocompleteResult
    {
        $matches = [];
        $seen = [];

        foreach ($this->bucket->items as $name => $prompt) {
            // Skip aliases if we've already seen this prompt
            if (in_array($prompt->name, $seen, true)) {
                continue;
            }

            $seen[] = $prompt->name;

            // Add leading slash if partial has it
            $displayName = $partial && str_starts_with($partial, '/')
                ? "/{$prompt->name}"
                : $prompt->name;

            $searchName = ltrim($partial, '/');

            // Match if starts with partial
            if ($searchName === '' || str_starts_with($prompt->name, $searchName)) {
                $matches[] = [
                    'value' => $displayName,
                    'description' => $prompt->description ?? '',
                    'type' => 'prompt',
                ];
            }
        }

        return new AutocompleteResult($matches);
    }

    /**
     * Get option completions for a prompt.
     */
    private function getOptionCompletions(string $commandName, string $partial): AutocompleteResult
    {
        // Remove leading slash if present
        $commandName = ltrim($commandName, '/');

        /** @var Prompt|null $prompt */
        $prompt = $this->bucket->items->get($commandName);

        if ($prompt === null) {
            return new AutocompleteResult([]);
        }

        $matches = [];

        foreach ($prompt->options as $option) {
            // Match if starts with partial
            if ($partial === '' || str_starts_with($option->name, $partial)) {
                $matches[] = [
                    'value' => "--{$option->name}",
                    'description' => $option->description ?? '',
                    'type' => 'option',
                ];
            }
        }

        return new AutocompleteResult($matches);
    }

    /**
     * Get value completions for an option.
     */
    private function getValueCompletions(string $commandName, string $optionName, string $partial): AutocompleteResult
    {
        // Remove leading slash if present
        $commandName = ltrim($commandName, '/');

        /** @var Prompt|null $prompt */
        $prompt = $this->bucket->items->get($commandName);

        if ($prompt === null) {
            return new AutocompleteResult([]);
        }

        // Find the option
        $option = null;
        foreach ($prompt->options as $opt) {
            if ($opt->name === $optionName) {
                $option = $opt;
                break;
            }
        }

        if ($option === null || $option->autocomplete === null) {
            return new AutocompleteResult([]);
        }

        $matches = [];

        // Handle array autocomplete
        if (is_array($option->autocomplete)) {
            foreach ($option->autocomplete as $value) {
                if ($partial === '' || str_starts_with((string)$value, $partial)) {
                    $matches[] = [
                        'value' => (string)$value,
                        'description' => '',
                        'type' => 'value',
                    ];
                }
            }
        }

        // Handle closure autocomplete
        if ($option->autocomplete instanceof \Closure) {
            $values = ($option->autocomplete)($partial);
            foreach ($values as $value) {
                $matches[] = [
                    'value' => (string)$value,
                    'description' => '',
                    'type' => 'value',
                ];
            }
        }

        return new AutocompleteResult($matches);
    }
}

/**
 * Autocomplete result containing matches and context.
 */
final readonly class AutocompleteResult
{
    public function __construct(
        public array $matches,
    )
    {
    }

    public function isEmpty(): bool
    {
        return empty($this->matches);
    }

    public function count(): int
    {
        return count($this->matches);
    }

    public function getSingleMatch(): ?string
    {
        if ($this->count() === 1) {
            return $this->matches[0]['value'];
        }

        return null;
    }

    /**
     * Get the common prefix of all matches.
     */
    public function getCommonPrefix(): ?string
    {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->count() === 1) {
            return $this->matches[0]['value'];
        }

        // Find common prefix
        $values = array_column($this->matches, 'value');
        $prefix = $values[0];

        foreach ($values as $value) {
            $prefixLen = 0;
            $minLen = min(strlen($prefix), strlen($value));

            for ($i = 0; $i < $minLen; $i++) {
                if ($prefix[$i] === $value[$i]) {
                    $prefixLen++;
                } else {
                    break;
                }
            }

            $prefix = substr($prefix, 0, $prefixLen);

            if ($prefix === '') {
                return null;
            }
        }

        return $prefix;
    }
}
