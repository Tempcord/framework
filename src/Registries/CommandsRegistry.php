<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\Extension\Extension;
use Ragnarok\Fenrir\FilteredEventEmitter;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Tempcord\Support\Commands\CommandsBucket;
use Tempcord\TempcordConfig;
use Tempest\Container\Singleton;

#[Singleton]
class CommandsRegistry implements Extension
{
    private Discord $discord;

    public function __construct(
        protected(set) CommandsBucket $bucket,
        private readonly TempcordConfig $config
    )
    {
    }

    public function initialize(Discord $discord): void
    {
        $this->discord = $discord;
        $this->registerCommands();

        $commandListener = new FilteredEventEmitter(
            $discord->gateway->events,
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interactionCreate) => $interactionCreate?->type === InteractionType::APPLICATION_COMMAND
        );

        $commandListener->on(Events::INTERACTION_CREATE,
            fn(InteractionCreate $interactionCreate) => $this->bucket->handle($interactionCreate)
        );

        $commandListener->start();
    }

    private function registerCommands(): void
    {
        foreach ($this->bucket->items->toArray() as $command) {
            $this->discord->gateway->events->once(
                Events::READY,
                function ($ready) use ($command) {
                    $guildId = $command->guildId ?? $this->config->guildId;

                    if ($guildId !== null) {
                        $this->discord->rest->guildCommand->createApplicationCommand(
                            $ready->user->id,
                            $guildId,
                            $command->builder
                        );
                    } else {
                        $this->discord->rest->globalCommand->createApplicationCommand(
                            $ready->user->id,
                            $command->builder
                        );
                    }
                }
            );
        }
    }
}