<?php

namespace Tempcord\Support\Responses\Decorators;

use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Channel\EmbedBuilder;
use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\Factory;
use Tempcord\Support\Responses\ResponseDecorator;

class WarningEmbed implements ResponseDecorator
{
    public function decorate(CommandInteraction $interaction, InteractionCallbackBuilder $builder): Factory
    {
        if ($builder->hasEmbeds()) {
            foreach ($builder->getEmbeds() as $embed) {
                $embed->color = 0xFFA500; // Orange color for warning
                $embed->setTitle("Warning");
                $embed->description = $embed->getDescription() ?? $builder->getContent() ?? 'Warning';
            }
        } else {
            $builder->addEmbed(
                embed: new EmbedBuilder()
                    ->setColor(0xFFA500)
                    ->setTitle("Warning")
                    ->setDescription($builder->getContent() ?? 'Warning')
            );
        }

        $builder->setContent('');

        return $interaction->respondWith($builder);
    }
}