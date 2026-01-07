<?php

namespace Tempcord\Support\Responses\Decorators;

use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Footer implements ResponseDecorator
{
    public function __construct(
        private string $text,
        private ?string $iconUrl = null
    )
    {
    }

    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        $response->footer($this->text, $this->iconUrl);
    }
}
