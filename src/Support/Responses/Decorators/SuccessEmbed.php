<?php

namespace Tempcord\Support\Responses\Decorators;

use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Channel\EmbedBuilder;
use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\Factory;
use Tempcord\Support\Responses\ResponseDecorator;

class SuccessEmbed implements ResponseDecorator
{
    public function decorate(CommandInteraction $interaction, InteractionCallbackBuilder $builder): Factory
    {
        if ($builder->hasEmbeds()) {
            foreach ($builder->getEmbeds() as $embed) {
                $embed->color = 0x00FF00; // Green color for success
            }
        } else {
            $builder->addEmbed(
                embed: new EmbedBuilder()
                    ->setColor(0x00FF00)
            );
        }

        return $interaction->respondWith($builder);
    }
}