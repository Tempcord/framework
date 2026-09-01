<?php

namespace Tempcord\Enums;

use CyberWolf\Discord\Enums\InteractionType;
use CyberWolf\Discord\Enums\MessageComponentType;
use CyberWolf\Discord\Gateway\Events\InteractionCreate;

/**
 * The kind of component interaction a handler answers.
 *
 * Discord reports a button press, a select menu choice and a submitted modal
 * under one gateway event, told apart only by the interaction type and the
 * component type beneath it. This enum is that distinction, named.
 */
enum ComponentKind: string
{
    case Button = 'button';
    case SelectMenu = 'select_menu';
    case ModalSubmit = 'modal_submit';

    /**
     * Which kind an incoming interaction is, or null when it is not a component
     * interaction at all.
     */
    public static function of(InteractionCreate $interaction): ?self
    {
        if (!isset($interaction->type)) {
            return null;
        }

        return match ($interaction->type) {
            InteractionType::MODAL_SUBMIT => self::ModalSubmit,
            InteractionType::MESSAGE_COMPONENT => match ($interaction->data->component_type ?? null) {
                MessageComponentType::BUTTON => self::Button,
                MessageComponentType::STRING_SELECT,
                MessageComponentType::USER_SELECT,
                MessageComponentType::ROLE_SELECT,
                MessageComponentType::MENTIONABLE_SELECT,
                MessageComponentType::CHANNEL_SELECT => self::SelectMenu,
                default => null,
            },
            default => null,
        };
    }
}
