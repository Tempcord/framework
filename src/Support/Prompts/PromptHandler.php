<?php

namespace Tempcord\Support\Prompts;

use Tempcord\Attributes\Prompts\Prompt;
use Tempcord\Attributes\Prompts\PromptOption;
use Tempest\Console\Console;
use Tempest\Reflection\MethodReflector;
use Throwable;
use function Tempest\get;
use function Tempest\invoke;

readonly class PromptHandler
{
    private mixed $promptInstance;

    public function __construct(
        private(set) Prompt           $prompt,
        private(set) ?MethodReflector $method,
    )
    {
        // Cache prompt instance to avoid repeated container lookups
        $this->promptInstance = get($this->prompt->reflector->getName());
    }

    /**
     * Handle the prompt execution.
     *
     * @param array<string, mixed> $arguments Parsed arguments from user input
     * @param Console $console Console instance for output
     * @throws Throwable
     */
    public function handle(array $arguments, Console $console): void
    {
        $logger = get(\Tempest\Log\Logger::class);

        try {
            // Map arguments to method parameters
            $args = $this->mapArguments($arguments, $console);

            // Execute prompt handler
            invoke($this->method, $this->promptInstance, ...$args);
        } catch (Throwable $e) {
            $logger->error("Prompt '{$this->prompt->name}' failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Map parsed arguments to method parameters.
     *
     * @param array<string, mixed> $arguments
     * @param Console $console
     * @return array<string, mixed>
     */
    private function mapArguments(array $arguments, Console $console): array
    {
        $args = [];

        if (!$this->method) {
            return $args;
        }

        foreach ($this->method->getParameters() as $parameter) {
            $paramName = $parameter->getName();

            // Special handling for Console parameter
            if ($parameter->getType()?->getName() === Console::class) {
                $args[$paramName] = $console;
                continue;
            }

            // Check if parameter has PromptOption attribute
            $promptOption = $parameter->getAttribute(PromptOption::class);

            if ($promptOption) {
                $promptOption = $promptOption->withReflector($parameter);
                $optionName = $promptOption->name;

                // Get value from parsed arguments
                $value = $arguments[$optionName] ?? null;

                // Use default value if not provided and parameter is optional
                if ($value === null && $parameter->isOptional()) {
                    $value = $parameter->getDefaultValue();
                }

                // Map value to correct type
                $args[$paramName] = $promptOption->mapValue($value);
            } else {
                // Parameter without PromptOption - try to match by name
                if (isset($arguments[$paramName])) {
                    $args[$paramName] = $arguments[$paramName];
                } elseif ($parameter->isOptional()) {
                    $args[$paramName] = $parameter->getDefaultValue();
                } else {
                    // Required parameter but not provided - use null
                    $args[$paramName] = null;
                }
            }
        }

        return $args;
    }
}
