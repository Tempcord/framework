<?php

namespace Tempcord\Support\Responses\Decorators;

use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\InteractionResponse;
use Tempcord\Support\Responses\ResponseDecorator;

readonly class Field implements ResponseDecorator
{
    public function __construct(
        private string $name,
        private string $value,
        private bool $inline = false
    )
    {
    }

    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void
    {
        $response->field($this->name, $this->value, $this->inline);
    }
}
