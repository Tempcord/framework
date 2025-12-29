<?php

namespace Tempcord\Support\Commands;

use Ragnarok\Fenrir\Discord;
use Tempcord\Attributes\Commands\Command;
use Tempcord\CommandInteraction;
use Tempest\Support\Arr\ImmutableArray;
use function Tempest\get;

class CommandsBucket
{
    /** @var ImmutableArray<string, Command> */
    protected(set) ImmutableArray $items;

    public function __construct()
    {
        $this->items = new ImmutableArray();
    }

    public function add(Command $command): void
    {
        $this->items = $this->items->put($command->name, $command);
    }


    /**
     * @throws \Throwable
     */
    public function handle(\Ragnarok\Fenrir\Gateway\Events\InteractionCreate $interactionCreate): void
    {
        /** @var Command $command */
        $command = $this->items->get($interactionCreate->data->name);
        $command->handler->handle(new CommandInteraction(
            interaction: $interactionCreate,
            discord: get(Discord::class)
        ));
    }
}