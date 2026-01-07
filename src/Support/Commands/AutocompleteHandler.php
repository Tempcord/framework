<?php

declare(strict_types=1);

namespace Tempcord\Support\Commands;

use Closure;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempest\Log\Logger;
use function Tempest\get;

/**
 * Handles autocomplete interactions for command options.
 *
 * Finds the focused option, calls its autocomplete handler, and returns choices.
 */
class AutocompleteHandler
{
    public function __construct(
        private readonly Command $command,
        private readonly Discord $discord,
        private readonly Logger $logger,
    ) {}

    /**
     * Handle an autocomplete interaction.
     */
    public function handle(InteractionCreate $interaction): void
    {
        $focused = $this->findFocusedOption($interaction->data->options ?? []);

        if ($focused === null) {
            $this->logger->warning('Autocomplete: No focused option found');
            return;
        }

        $optionAttribute = $this->findOptionAttribute($focused->name, $interaction->data->options ?? []);

        if ($optionAttribute === null || $optionAttribute->autocomplete === null) {
            $this->logger->warning("Autocomplete: No handler for option '{$focused->name}'");
            $this->sendEmptyResponse($interaction);
            return;
        }

        try {
            $currentValue = $focused->value ?? '';
            $handler = $optionAttribute->autocomplete;

            // Call the autocomplete handler
            $results = $this->callHandler($handler, $currentValue, $interaction);

            // Build and send response
            $choices = $this->buildChoices($results);
            $this->sendResponse($interaction, $choices);

        } catch (\Throwable $e) {
            $this->logger->error("Autocomplete failed: {$e->getMessage()}", [
                'option' => $focused->name,
                'exception' => $e,
            ]);
            $this->sendEmptyResponse($interaction);
        }
    }

    /**
     * Find the focused option in the interaction data.
     */
    private function findFocusedOption(array $options, ?string $subcommandPath = null): ?ApplicationCommandInteractionDataOptionStructure
    {
        foreach ($options as $option) {
            // Handle subcommand groups and subcommands
            if (!empty($option->options)) {
                $found = $this->findFocusedOption($option->options, $option->name);
                if ($found !== null) {
                    return $found;
                }
            }

            // Check if this option is focused
            if ($option->focused ?? false) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Find the Option attribute for the focused option.
     */
    private function findOptionAttribute(string $optionName, array $interactionOptions): ?Option
    {
        // Check if we're in a subcommand
        $subcommandName = $this->getSubcommandPath($interactionOptions);

        if ($subcommandName !== null) {
            // Find in subcommand options
            $subcommand = $this->command->getSubCommand($subcommandName);
            if ($subcommand !== null) {
                foreach ($subcommand->options as $option) {
                    if ($option->name === $optionName) {
                        return $option;
                    }
                }
            }
        } else {
            // Find in command options
            foreach ($this->command->options as $option) {
                if ($option->name === $optionName) {
                    return $option;
                }
            }
        }

        return null;
    }

    /**
     * Get the subcommand path from interaction options.
     */
    private function getSubcommandPath(array $options): ?string
    {
        foreach ($options as $option) {
            // Type 1 = SUB_COMMAND, Type 2 = SUB_COMMAND_GROUP
            if (($option->type ?? 0) === 1) {
                return $option->name;
            }
            if (($option->type ?? 0) === 2 && !empty($option->options)) {
                foreach ($option->options as $subOption) {
                    if (($subOption->type ?? 0) === 1) {
                        return "{$option->name}:{$subOption->name}";
                    }
                }
            }
        }

        return null;
    }

    /**
     * Call the autocomplete handler.
     */
    private function callHandler(Closure $handler, string $value, InteractionCreate $interaction): mixed
    {
        // Get handler parameter count to determine how to call it
        $reflection = new \ReflectionFunction($handler);
        $paramCount = $reflection->getNumberOfParameters();

        return match ($paramCount) {
            0 => $handler(),
            1 => $handler($value),
            default => $handler($value, $interaction),
        };
    }

    /**
     * Build choices array from handler results.
     *
     * Supports:
     * - Simple array: ['foo', 'bar'] -> [['name' => 'foo', 'value' => 'foo'], ...]
     * - Associative array: ['Display' => 'value'] -> [['name' => 'Display', 'value' => 'value'], ...]
     * - Pre-formatted: [['name' => 'Foo', 'value' => 'foo'], ...]
     */
    private function buildChoices(mixed $results): array
    {
        if (!is_array($results)) {
            return [];
        }

        $choices = [];

        foreach ($results as $key => $value) {
            // Already formatted as choice
            if (is_array($value) && isset($value['name'], $value['value'])) {
                $choices[] = [
                    'name' => (string) $value['name'],
                    'value' => $value['value'],
                ];
                continue;
            }

            // Associative array: key is display name, value is the value
            if (is_string($key) && !is_numeric($key)) {
                $choices[] = [
                    'name' => $key,
                    'value' => $value,
                ];
                continue;
            }

            // Simple value: use as both name and value
            $choices[] = [
                'name' => (string) $value,
                'value' => $value,
            ];
        }

        // Discord limits to 25 choices
        return array_slice($choices, 0, 25);
    }

    /**
     * Send autocomplete response with choices.
     */
    private function sendResponse(InteractionCreate $interaction, array $choices): void
    {
        $callback = InteractionCallbackBuilder::new()
            ->setType(8) // APPLICATION_COMMAND_AUTOCOMPLETE_RESULT
            ->setChoices($choices);

        $this->discord->rest->webhook->createInteractionResponse(
            $interaction->id,
            $interaction->token,
            $callback
        );
    }

    /**
     * Send empty autocomplete response.
     */
    private function sendEmptyResponse(InteractionCreate $interaction): void
    {
        $this->sendResponse($interaction, []);
    }
}
