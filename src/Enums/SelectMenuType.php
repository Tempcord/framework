<?php

declare(strict_types=1);

namespace Tempcord\Enums;

/**
 * Discord Select Menu Types
 * @see https://discord.com/developers/docs/interactions/message-components#component-object-component-types
 */
enum SelectMenuType: int
{
    case StringSelect = 3;  // Select from predefined text options
    case UserSelect = 5;    // Select users
    case RoleSelect = 6;    // Select roles
    case MentionableSelect = 7; // Select users and roles
    case ChannelSelect = 8; // Select channels
}
