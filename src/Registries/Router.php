<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommand;
use Tempcord\Attributes\Commands\Command;
use Tempest\Container\Singleton;
use function React\Async\async;
use function Tempest\get;

#[Singleton]
class Router
{
    /**
     * Registered commands map
     *
     * Keys are discord ID
     * Values are handlers
     *
     * @var array<string, Command>
     */
    private(set) array $commands = [];

    /**
     * Determine if Router can handle command
     *
     * Command should be registered in commands
     */
    public function canHandle(InteractionCreate $event): bool
    {
        return isset($this->commands[$event->data->id]);
    }

    /**
     * Register Command mapped to ApplicationCommand id
     *
     * @return $this
     */
    public function register(ApplicationCommand $applicationCommand, Command $command): self
    {
        $this->commands[$applicationCommand->id] = $command;
        return $this;
    }

    /**
     * Handle incoming CommandInteraction event
     */
    public function handle(CommandInteraction $event): void
    {
        if (!array_key_exists($event->interaction->data->id, $this->commands)) {
            throw new \RuntimeException('Command "' . $event->interaction->data->id . '" not found in router');
        }

        $command = $this->commands[$event->interaction->data->id];

        try {
            $command->handler->handle($event);
        } catch (\Throwable $exception) {
            dd($exception);
        }
    }
}