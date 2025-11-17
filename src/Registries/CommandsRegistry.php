<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\Extension\Extension;
use Ragnarok\Fenrir\FilteredEventEmitter;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Parts\ApplicationCommand;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Support\Commands\CommandsBucket;
use Tempest\Container\Singleton;
use Tempest\Reflection\MethodReflector;
use function React\Async\await;

#[Singleton]
class CommandsRegistry implements Extension
{
    /** @var array<string, MethodReflector> */
    private array $handlersCommand = [];
    private Discord $discord;

    public function __construct(
        protected(set) CommandsBucket $bucket
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

//        $commandListener->on(Events::INTERACTION_CREATE,
//            fn(InteractionCreate $interactionCreate) => )
//        );
        $commandListener->start();
    }

    private function registerCommands(): void
    {

        dd(await($this->discord->rest->guildCommand->getCommands('932734827545903104', '1318554685145350145')));


        foreach ($this->bucket->items->toArray() as $command) {
            /** @var Command $command */
           try{
               $this->discord->gateway->events->once(
                   Events::READY,
                   function ($ready) use ($command) {
                       $this->discord->rest->guildCommand->createApplicationCommand(
                           $ready->user->id,
                           '932734827545903104',
                           $command->builder
                       );
                   }
               );
           }catch (\Throwable $e){
               dd($e);
           }
//            if ($command->guildId) {
//                $this->registerGuildCommand($command->builder, (string)$command->guildId);
//            } else {
//                $this->registerGlobalCommand($command);
//            }
        }
    }


//    public function registerGuildCommand(Command $command, string $guildId): void
//    {
//        $this->events->once(
//            Events::READY,
//            function ($ready) use ($guildId, $command) {
//                $this->registrar->registerGuild(
//                    $ready->user->id,
//                    $guildId,
//                    $command->builder
//                )->then(function (ApplicationCommand $applicationCommand) use ($command) {
//                    $this->router->register($applicationCommand, $command);
//                });
//            }
//        );
//    }
//
//
//    public function registerGlobalCommand(Command $command): void
//    {
//        $this->events->once(
//            Events::READY,
//            function (Ready $ready) use ($command) {
//                $this->registrar->registerGlobal(
//                    $ready->user->id,
//                    $command->builder
//                )->then(function (ApplicationCommand $applicationCommand) use ($command) {
//                    $this->router->register($applicationCommand, $command);
//                });
//            }
//        );
//    }
}