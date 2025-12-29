<?php

namespace Tempcord\Support\Responses\Decorators;

use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\Factory;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Content implements ResponseDecorator
{
    public function __construct(
        private string $content
    )
    {
    }

    public function decorate(CommandInteraction $interaction, InteractionCallbackBuilder $builder): Factory
    {
        if ($builder->hasEmbeds()) {
            foreach ($builder->getEmbeds() as $embed) {
                $embed->setDescription($this->content);
            }
        } else {
            $builder->setContent($this->content);
        }

        return $interaction->respondWith($builder);
    }
}