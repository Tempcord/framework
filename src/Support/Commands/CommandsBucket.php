<?php

namespace Tempcord\Support\Commands;

use Ragnarok\Fenrir\Discord;
use Tempcord\Attributes\Commands\Command;
use Tempcord\CommandInteraction;
use Tempcord\TempcordConfig;
use Tempest\Log\Logger;
use Tempest\Support\Arr\ImmutableArray;
use Throwable;
use function Tempest\get;

class CommandsBucket
{
    /** @var ImmutableArray<string, Command> */
    protected(set) ImmutableArray $items;

    private Logger $logger;
    private Discord $discord;
    private TempcordConfig $config;

    public function __construct()
    {
        $this->items = new ImmutableArray();

    }

    public function add(Command $command): void
    {
        $this->items = $this->items->put($command->name, $command);
    }

    public function handle(\Ragnarok\Fenrir\Gateway\Events\InteractionCreate $interactionCreate): void
    {
        //@todo: refactor to better approach (do not use CommandsRegistry inside CommandsDiscovery)
        $this->logger = get(Logger::class);
        $this->discord = get(Discord::class);
        $this->config = get(TempcordConfig::class);
        $commandName = $interactionCreate->data->name;

        /** @var Command|null $command */
        $command = $this->items->get($commandName);

        if ($command === null) {
            $this->logger->warning("Unknown command received: {$commandName}");
            return;
        }

        $interaction = new CommandInteraction(
            interaction: $interactionCreate,
            discord: $this->discord,
            config: $this->config
        );

        try {
            $command->handler->handle($interaction);
        } catch (Throwable $e) {
            $this->logger->error("Command '{$commandName}' failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            $interaction->respond()
                ->error()
                ->content('An error occurred while processing your command.')
                ->send();
        }
    }
}