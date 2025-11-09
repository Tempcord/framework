<?php

namespace Tempcord\Support\Commands;

use Tempcord\Attributes\Command;
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
        $this->items = $this->items->put($command->name, $command);
    }
}