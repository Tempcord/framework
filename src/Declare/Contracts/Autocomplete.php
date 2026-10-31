<?php

namespace Tempcord\Declare\Contracts;

use Tempcord\Discord\Interaction\CommandInteraction;

interface Autocomplete
{
    public function handle(CommandInteraction $interaction, mixed $value): mixed;

}