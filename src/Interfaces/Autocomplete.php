<?php

namespace Tempcord\Interfaces;

use Ragnarok\Fenrir\Interaction\CommandInteraction;

interface Autocomplete
{
    public function handle(CommandInteraction $interaction, mixed $value): mixed;

}