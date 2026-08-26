<?php

namespace Tempcord\Interfaces;

use CyberWolf\Discord\Interaction\CommandInteraction;

interface Autocomplete
{
    public function handle(CommandInteraction $interaction, mixed $value): mixed;

}