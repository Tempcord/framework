<?php

namespace Tempcord\Support\Responses\Decorators;

use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Channel\EmbedBuilder;
use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\Factory;
use Tempcord\Support\Responses\ResponseDecorator;

class ErrorEmbed implements ResponseDecorator
{
    public function decorate(CommandInteraction $interaction, InteractionCallbackBuilder $builder): Factory
    {
        if ($builder->hasEmbeds()) {
            foreach ($builder->getEmbeds() as $embed) {
                $embed->color = 0xFF0000; // Red color for error
                $embed->description = $embed->description ?? $builder->getContent();
            }
        } else {
            $builder->addEmbed(
                embed: new EmbedBuilder()
                    ->setColor(0xFF0000)
                    ->setDescription($builder->getContent())
            );
        }

        $builder->setContent(null);

        return $interaction->respondWith($builder);
    }
}