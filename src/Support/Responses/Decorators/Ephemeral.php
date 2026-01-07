<?php

namespace Tempcord\Support\Responses\Decorators;

use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Ephemeral implements ResponseDecorator
{
    public function __construct(
        private bool $ephemeral = true
    )
    {
    }

    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        $response->ephemeral($this->ephemeral);
    }
}
