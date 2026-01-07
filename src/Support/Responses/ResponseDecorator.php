<?php

namespace Tempcord\Support\Responses;

use Tempcord\CommandInteraction;

interface ResponseDecorator
{
    /**
     * Decorate the response in-place
     *
     * @param InteractionResponse $response The response to decorate
     * @param CommandInteraction $interaction The interaction (for context)
     * @return void
     */
    public function decorate(InteractionResponse $response, CommandInteraction $interaction): void;
}