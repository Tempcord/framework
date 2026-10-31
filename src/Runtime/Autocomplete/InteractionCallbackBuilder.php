<?php

namespace Tempcord\Runtime\Autocomplete;

use Discord\Http\Multipart\MultipartBody;
use Tempcord\Discord\Enums\InteractionCallbackType;

final class InteractionCallbackBuilder extends \Tempcord\Discord\Interaction\Helpers\InteractionCallbackBuilder
{
    public array $choices = [];

    public function setChoices(array $choices): self
    {
        $this->choices = $choices;

        return $this;
    }

    public function get(): array|MultipartBody
    {
        $data = parent::get();

        if ($data instanceof MultipartBody) {
            return $data;
        }

        /*
         * An autocomplete result always carries its choices, even none:
         * Discord rejects one without data.choices (50035, BASE_TYPE_REQUIRED),
         * and an empty list is how a handler says nothing matched.
         */
        if ($this->choices !== [] || $this->getType() === InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT) {
            $data['data']['choices'] = $this->choices;
        }

        return $data;
    }
}
