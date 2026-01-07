<?php

namespace Tempcord\Support\Responses\Decorators;

use Carbon\Carbon;
use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Timestamp implements ResponseDecorator
{
    public function __construct(
        private ?Carbon $timestamp = null
    )
    {
    }

    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        $response->timestamp($this->timestamp);
    }
}
