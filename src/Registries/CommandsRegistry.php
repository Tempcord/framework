<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\Extension\Extension;
use Ragnarok\Fenrir\FilteredEventEmitter;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Gateway\Events\Ready;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommand;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Tempcord\Attributes\Command;
use Tempcord\Support\Commands\CommandsBucket;
use Tempest\Container\Singleton;
use Tempest\Reflection\MethodReflector;
use function Tempest\get;

#[Singleton]
class CommandsRegistry implements Extension
{
    private Discord $discord;

    private array $commandRegisters;

    /** @var array<string, MethodReflector> */
    private array $handlersCommand = [];
    protected(set) CommandsBucket $bucket;

    public function __construct()
    {
        $this->bucket = new CommandsBucket();
    }

    public function initialize(Discord $discord): void
    {
        $this->discord = $discord;

        $this->registerCommands();

        $commandListener = new FilteredEventEmitter(
            $this->discord->gateway->events,
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interactionCreate) => $interactionCreate?->type === InteractionType::APPLICATION_COMMAND
                && isset($this->handlersCommand[$interactionCreate->data->id])
        );

        $commandListener->on(Events::INTERACTION_CREATE, $this->handleCommandInteraction(...));
        $commandListener->start();
    }

    private function handleCommandInteraction(InteractionCreate $interactionCreate): void
    {
        $firedCommand = new CommandInteraction($interactionCreate, $this->discord);
        $handler = $this->handlersCommand[$interactionCreate->data->id];

        $handler->invokeArgs(get($handler->getDeclaringClass()->getName()), [$firedCommand]);
    }


    private function registerCommands(): void
    {
        dd($this->bucket->items);
//        array_walk($this->commandRegisters, static function ($register) {
//            $register();
//        });
    }


    public function registerGuildCommand(CommandBuilder $commandBuilder, string $guildId, MethodReflector $handler): void
    {
        $this->commandRegisters[] = function () use ($commandBuilder, $handler, $guildId) {
            /** Ready event includes Application ID */
            $this->discord->gateway->events->once(
                Events::READY,
                function (Ready $ready) use ($guildId, $commandBuilder, $handler) {
                    $this->discord->rest->guildCommand->createApplicationCommand(
                        $ready->user->id,
                        $guildId,
                        $commandBuilder
                    )->then(function (ApplicationCommand $applicationCommand) use ($handler) {
                        $this->handlersCommand[$applicationCommand->id] = $handler;
                    });
                }
            );
        };
    }


    public function registerGlobalCommand(CommandBuilder $commandBuilder, MethodReflector $handler): void
    {
        $this->commandRegisters[] = function () use ($commandBuilder, $handler) {
            /** Ready event includes Application ID */
            $this->discord->gateway->events->once(
                Events::READY,
                function (Ready $ready) use ($commandBuilder, $handler) {
                    $this->discord->rest->globalCommand->createApplicationCommand(
                        $ready->user->id,
                        $commandBuilder
                    )->then(function (ApplicationCommand $applicationCommand) use ($handler) {
                        $this->handlersCommand[$applicationCommand->id] = $handler;
                    });
                }
            );
        };
    }


}