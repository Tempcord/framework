<?php

namespace Tempcord\Tests\Doubles;

use Tempcord\Discord\Enums\InteractionType;
use Tempcord\Discord\Enums\MessageComponentType;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Parts\InteractionData;

/**
 * The interaction payloads Discord sends for components, built by hand so tests
 * can push them through the gateway emitter exactly as the real thing arrives.
 */
final class Interactions
{
    public static function button(string $customId): InteractionCreate
    {
        return self::component($customId, MessageComponentType::BUTTON);
    }

    /**
     * @param list<string> $values
     */
    public static function selectMenu(string $customId, array $values = []): InteractionCreate
    {
        $interaction = self::component($customId, MessageComponentType::STRING_SELECT);
        $interaction->data->values = $values;

        return $interaction;
    }

    /**
     * @param array<string, string> $fields keyed by the field's own custom id
     */
    public static function modal(string $customId, array $fields = []): InteractionCreate
    {
        $rows = [];

        foreach ($fields as $field => $value) {
            $rows[] = [
                'type' => MessageComponentType::ACTION_ROW->value,
                'components' => [[
                    'type' => MessageComponentType::TEXT_INPUT->value,
                    'custom_id' => $field,
                    'value' => $value,
                ]],
            ];
        }

        $interaction = self::interaction(InteractionType::MODAL_SUBMIT, $customId);
        $interaction->data->components = $rows;

        return $interaction;
    }

    private static function component(string $customId, MessageComponentType $type): InteractionCreate
    {
        $interaction = self::interaction(InteractionType::MESSAGE_COMPONENT, $customId);
        $interaction->data->component_type = $type;

        return $interaction;
    }

    private static function interaction(InteractionType $type, string $customId): InteractionCreate
    {
        $data = new InteractionData();
        $data->custom_id = $customId;

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = '::token::';
        $interaction->type = $type;
        $interaction->data = $data;

        return $interaction;
    }
}
