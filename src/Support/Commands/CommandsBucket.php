<?php

namespace Tempcord\Support\Commands;

use Tempcord\Attributes\Commands\Command;
use Tempest\Support\Arr\ImmutableArray;

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
        if ($this->items->hasKey($command->name)) {
            /** @var Command $existingCommand */
            $existingCommand = $this->items[$command->name];
            $existingCommand->merge($command);
        } else {
            $this->items = $this->items->put($command->name, $command);
        }
    }
}