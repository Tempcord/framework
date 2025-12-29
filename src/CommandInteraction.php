<?php

namespace Tempcord;

use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\Support\Responses\Factory;

class CommandInteraction extends \Ragnarok\Fenrir\Interaction\CommandInteraction
{
    public function respondWith(InteractionCallbackBuilder $builder = new InteractionCallbackBuilder): Factory
    {
        return new Factory(
            interaction: $this,
            builder: $builder
        );
    }

}