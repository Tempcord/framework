<?php

declare(strict_types=1);

namespace Tempcord\Enums;

/**
 * Discord Component Types
 * @see https://discord.com/developers/docs/interactions/message-components#component-object-component-types
 */
enum ComponentType: int
{
    case ActionRow = 1;
    case Button = 2;
    case StringSelect = 3;
    case TextInput = 4;
    case UserSelect = 5;
    case RoleSelect = 6;
    case MentionableSelect = 7;
    case ChannelSelect = 8;
}
