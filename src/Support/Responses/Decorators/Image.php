<?php

namespace Tempcord\Support\Responses\Decorators;

use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Image implements ResponseDecorator
{
    public function __construct(
        private string $url
    )
    {
    }

    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        $response->image($this->url);
    }
}
