<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\Support\Responses\InteractionResponse;

class CommandInteraction extends \Ragnarok\Fenrir\Interaction\CommandInteraction
{
    public function __construct(InteractionCreate $interaction, Discord $discord, private readonly TempcordConfig $config)
    {
        parent::__construct($interaction, $discord);
    }

    public function respond(InteractionCallbackBuilder $with = new InteractionCallbackBuilder): InteractionResponse
    {
        $response = new InteractionResponse(
            interaction: $this,
            builder: $with
        );

        // Apply branding if configured
        $this->config->branding?->decorate($response, $this);

        return $response;
    }
}