<?php

namespace Tempcord\Support\Responses\Decorators;

use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

class WarningEmbed implements ResponseDecorator
{
    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        $response->warning();

        // If content was set as plain text, move it to embed
        $builder = $response->getBuilder();
        $content = $builder->getContent();
        if ($content !== null && $content !== '') {
            $response->content($content);
            $builder->setContent(null);
        }
    }
}