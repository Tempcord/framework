<?php

namespace Tempcord\Support\Commands;

use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempcord\Attributes\Commands\Subcommand;
use Tempcord\CommandInteraction;
use Tempcord\Middleware\MiddlewarePipeline;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\TempcordConfig;
use Tempest\Container\Container;
use Tempest\Reflection\MethodReflector;
use Throwable;
use function React\Async\async;
use function Tempest\get;
use function Tempest\invoke;

readonly class CommandHandler
{
    private mixed $commandInstance;

    public function __construct(
        private(set) Command          $command,
        private(set) ?MethodReflector $method,
        private(set) TempcordConfig   $config,
    )
    {
        // Cache command instance to avoid repeated container lookups
        $this->commandInstance = get($this->command->reflector->getName());
    }

    /**
     * Merge middleware in execution order: Global → Command → Subcommand
     *
     * @param Subcommand|null $subcommand
     * @return array<class-string<CommandMiddleware>>
     */
    private function getMergedMiddleware(?Subcommand $subcommand): array
    {
        $middleware = [];

        // 1. Global middleware (from config)
        $middleware = array_merge($middleware, $this->config->globalMiddleware);

        // 2. Command-level middleware
        $middleware = array_merge($middleware, $this->command->middleware);

        // 3. Subcommand-level middleware (if handling subcommand)
        if ($subcommand !== null) {
            $middleware = array_merge($middleware, $subcommand->middleware);
        }

        return $middleware;
    }

    /**
     * @throws Throwable
     */
    public function handle(CommandInteraction $interaction): void
    {
        $handleStartTime = microtime(true);
        $logger = get(\Tempest\Log\Logger::class);

        $subCommandName = null;
        $subCommand = null;
        $options = $this->command->options;
        $handledBy = $this->method;

        if ($this->command->hasSubcommands) {
            $subCommandName = $interaction->getSubCommandName();
            $subCommand = $this->command->getSubCommand($subCommandName);

            if (!$subCommand) {
                $interaction->respond()
                    ->error()
                    ->content("Unknown subcommand: {$subCommandName}")
                    ->send();
                return;
            }

            $options = $subCommand->options;
            $handledBy = $subCommand->reflector;
        }

        $mappingStartTime = microtime(true);
        $this->mapArguments($options, $interaction, $subCommandName)()->then(function (array $args) use ($handledBy, $interaction, $subCommand, $mappingStartTime, $handleStartTime, $logger) {
            $mappingTime = round((microtime(true) - $mappingStartTime) * 1000, 2);
            $profiling = [
                'argument_mapping' => $mappingTime . 'ms'
            ];

            $executeCommand = function (CommandInteraction $int) use ($handledBy, $args, &$profiling): mixed {
                // Use cached command instance instead of repeated container lookups
                $start = microtime(true);
                $result = invoke($handledBy, $this->commandInstance, ...$args);
                $profiling['handler_execution'] = round((microtime(true) - $start) * 1000, 2) . 'ms';
                return $result;
            };

            $mergedMiddleware = $this->getMergedMiddleware($subCommand);

            if (!empty($mergedMiddleware)) {
                $start = microtime(true);
                $container = get(Container::class);
                $pipeline = new MiddlewarePipeline($container);
                $response = $pipeline
                    ->through($mergedMiddleware)
                    ->process($interaction, $executeCommand);
                $profiling['middleware_total'] = round((microtime(true) - $start) * 1000, 2) . 'ms';
            } else {
                $response = $executeCommand($interaction);
            }

            if ($response instanceof InteractionResponse) {
                $start = microtime(true);
                $response->send();
                $profiling['response_send'] = round((microtime(true) - $start) * 1000, 2) . 'ms';
            }

            $totalHandleTime = round((microtime(true) - $handleStartTime) * 1000, 2);
            $profiling['total_handle_time'] = $totalHandleTime . 'ms';

            $logger->info("⏱️  Command Performance Breakdown", $profiling);
        });
    }

    /**
     * @param array<Option> $options
     * @param CommandInteraction $interaction
     * @param string|null $subcommandName
     * @return callable
     */
    private function mapArguments(array $options, CommandInteraction $interaction, ?string $subcommandName): callable
    {
        return async(function () use ($interaction, $options, $subcommandName) {
            $logger = get(\Tempest\Log\Logger::class);
            $mappingStart = microtime(true);
            $optionTimings = [];

            $args = [
                'interaction' => $interaction
            ];

            $logger->debug("Mapping arguments", [
                'option_count' => count($options),
                'subcommand' => $subcommandName ?? 'none'
            ]);

            foreach ($options as $option) {
                $optionStart = microtime(true);
                $path = $subcommandName ? str_replace(':', '.', $subcommandName) . '.' . $option->name : $option->name;

                $args[$option->name] = $option->mapValue($interaction->getOption(
                    path: $path
                ), $interaction);

                $optionTime = round((microtime(true) - $optionStart) * 1000, 2);
                $optionTimings[$option->name] = $optionTime . 'ms';
            }

            $totalMappingTime = round((microtime(true) - $mappingStart) * 1000, 2);

            if (!empty($optionTimings)) {
                $logger->info("🔍 Option Mapping Breakdown", [
                    'options' => $optionTimings,
                    'total' => $totalMappingTime . 'ms'
                ]);
            }

            return $args;
        });

    }

}