<?php

namespace Tempcord\Support\Responses;

use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\CommandInteraction;

interface ResponseDecorator
{
    public function decorate(CommandInteraction $interaction, InteractionCallbackBuilder $builder): Factory;

}