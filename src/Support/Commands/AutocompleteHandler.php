<?php

declare(strict_types=1);

namespace Tempcord\Support\Commands;

use Closure;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\ApplicationCommandOptionChoice;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempcord\Registries\AutocompleteRegistry;
use Tempcord\Support\InteractionCallbackBuilder;
use Tempest\Log\Logger;
use function Tempest\get;

/**
 * Handles autocomplete interactions for command options.
 *
 * Finds the focused option, calls its autocomplete handler, and returns choices.
 */
readonly class AutocompleteHandler
{
    public function __construct(
        private Command $command,
        private Discord $discord,
        private Logger  $logger,
        private AutocompleteRegistry $autocompleteRegistry,
    )
    {
    }

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

            // Build and send a response
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
            if ($option->type === ApplicationCommandOptionType::SUB_COMMAND) {
                return $option->name;
            }
            if ($option->type === ApplicationCommandOptionType::SUB_COMMAND_GROUP && !empty($option->options)) {
                foreach ($option->options as $subOption) {
                    if ($subOption->type === ApplicationCommandOptionType::SUB_COMMAND) {
                        return "{$option->name}:{$subOption->name}";
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve and call the autocomplete handler.
     *
     * Supports:
     * - Closure: fn($value, $interaction) => [...]
     * - Class reference: DatabaseAutocomplete::class
     * - Array callable: [DatabaseAutocomplete::class, 'search']
     * - Instantiated object: new DatabaseAutocomplete()
     */
    private function callHandler(mixed $handler, string $value, InteractionCreate $interaction): mixed
    {
        // Case 1: Closure (existing behavior)
        if ($handler instanceof Closure) {
            $reflection = new \ReflectionFunction($handler);
            $paramCount = $reflection->getNumberOfParameters();

            return match ($paramCount) {
                0 => $handler(),
                1 => $handler($value),
                default => $handler($value, $interaction),
            };
        }

        // Case 2: Class reference (string)
        if (is_string($handler)) {
            $autocomplete = $this->autocompleteRegistry->getByClass($handler);

            if ($autocomplete === null) {
                throw new \RuntimeException(
                    "Autocomplete class '{$handler}' not found. Did you add #[Autocomplete] attribute?"
                );
            }

            $instance = get($handler); // Resolve from container as singleton
            return $instance($value, $interaction);
        }

        // Case 3: Array callable [Class::class, 'method']
        if (is_array($handler) && count($handler) === 2) {
            [$className, $method] = $handler;

            $autocomplete = $this->autocompleteRegistry->getByClass($className);

            if ($autocomplete === null) {
                throw new \RuntimeException(
                    "Autocomplete class '{$className}' not found. Did you add #[Autocomplete] attribute?"
                );
            }

            $instance = get($className);

            if (!method_exists($instance, $method)) {
                throw new \RuntimeException(
                    "Method '{$method}' does not exist on autocomplete class '{$className}'"
                );
            }

            return $instance->$method($value, $interaction);
        }

        // Case 4: Instantiated object
        if (is_object($handler)) {
            if (!method_exists($handler, '__invoke')) {
                throw new \RuntimeException(
                    'Autocomplete object must implement __invoke() method'
                );
            }

            return $handler($value, $interaction);
        }

        throw new \RuntimeException(
            'Invalid autocomplete handler type: ' . gettype($handler)
        );
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
                    'name' => (string)$value['name'],
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
                'name' => (string)$value,
                'value' => $value,
            ];
        }

        //TODO: Add name localization support

        $choices = array_map(static function(array $choice){
            $c = new ApplicationCommandOptionChoice();
            $c->name = $choice['name'];
            $c->value = $choice['value'];
            return $c;
        }, $choices);

        // Discord limits to 25 choices
        return array_slice($choices, 0, 25);
    }

    /**
     * Send autocomplete response with choices.
     */
    private function sendResponse(InteractionCreate $interaction, array $choices): void
    {
        $callback = InteractionCallbackBuilder::new()
            ->setChoices($choices)
            ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT);

        $this->discord->rest->webhook->createInteractionResponse(
            $interaction->id,
            $interaction->token,
            $callback
        );
    }

    /**
     * Send an empty autocomplete response.
     */
    private function sendEmptyResponse(InteractionCreate $interaction): void
    {
        $this->sendResponse($interaction, []);
    }
}
