<?php

namespace Tempcord\Support\Commands;

use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempest\Reflection\MethodReflector;
use Throwable;
use function React\Async\async;
use function Tempest\get;
use function Tempest\Support\Arr\is_empty;

readonly class CommandHandler
{

    public function __construct(
        private Command         $command,
        private MethodReflector $method
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(CommandInteraction $interaction): void
    {
        $subCommandName = null;
        $options = $this->command->options;
        $handledBy = $this->method;

//        if (!is_empty($this->command->subCommands)) {
//            $subCommandName = $interaction->getSubCommandName();
//            $subCommand = $this->command->subCommands[$subCommandName];
//            $options = $subCommand->options;
//            $handledBy = $subCommand->reflector;
//
//        }

        $this->mapArguments($options, $interaction, $subCommandName)()->then(function (array $args) use ($handledBy) {
            $command = get($this->command->reflector->getName());
            $handledBy->invokeArgs($command, $args);
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
            $args = [
                'interaction' => $interaction
            ];
            foreach ($options as $option) {
                $args[$option->name] = $option->mapValue($interaction->getOption(
                    path: $subcommandName ? str_replace(':', '.', $subcommandName) . '.' . $option->name : $option->name
                ), $interaction);
            }
            return $args;
        });

    }

}