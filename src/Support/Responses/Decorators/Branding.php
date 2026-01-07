<?php

namespace Tempcord\Support\Responses\Decorators;

use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Branding implements ResponseDecorator
{
    public function __construct(
        private ?string $icon = null,
        private ?string $text = null
    )
    {
    }

    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        if ($this->text !== null || $this->icon !== null) {
            $response->footer(
                text: $this->text ?? '',
                iconUrl: $this->icon
            );
        }
    }
}