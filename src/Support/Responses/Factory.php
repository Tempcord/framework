<?php

namespace Tempcord\Support\Responses;

use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\CommandInteraction;
use Tempcord\Support\Responses\Decorators\Content;
use Tempcord\Support\Responses\Decorators\ErrorEmbed;
use Tempcord\Support\Responses\Decorators\SuccessEmbed;
use Tempcord\Support\Responses\Decorators\WarningEmbed;

readonly class Factory
{
    public function __construct(
        private(set) CommandInteraction         $interaction,
        private(set) InteractionCallbackBuilder $builder = new InteractionCallbackBuilder
    )
    {
        $this->builder->setType(InteractionCallbackType::CHANNEL_MESSAGE_WITH_SOURCE);
    }

    public function success(): self
    {
        return $this->decorate(with: new SuccessEmbed);
    }

    public function warning(): self
    {
        return $this->decorate(with: new WarningEmbed);
    }

    public function error(): self
    {
        return $this->decorate(with: new ErrorEmbed);
    }

    public function content(string $content): self
    {
        return $this->decorate(with: new Content($content));
    }

    public function decorate(ResponseDecorator $with): self
    {
        return $with->decorate($this->interaction, $this->builder);
    }

}