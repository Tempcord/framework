<?php

namespace Tempcord\Support;

use Discord\Http\Multipart\MultipartBody;

final class InteractionCallbackBuilder extends \Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder
{
    public array $choices = [];

    public function setChoices(array $choices): self
    {
        $this->choices = $choices;

        return $this;
    }


    private function hasChoices(): bool
    {
        return count($this->choices) > 0;
    }

    public function get(): array|MultipartBody
    {
        $data = parent::get();

        if ($this->hasChoices()) {
            $data['data']['choices'] = $this->choices;
        }

        return $data;
    }

}