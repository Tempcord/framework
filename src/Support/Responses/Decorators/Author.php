<?php

namespace Tempcord\Support\Responses\Decorators;

use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Author implements ResponseDecorator
{
    public function __construct(
        private string $name,
        private ?string $url = null,
        private ?string $iconUrl = null
    )
    {
    }

    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        $response->author($this->name, $this->url, $this->iconUrl);
    }
}
