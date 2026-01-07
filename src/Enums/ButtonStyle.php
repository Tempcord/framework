<?php

declare(strict_types=1);

namespace Tempcord\Enums;

/**
 * Discord Button Styles
 * @see https://discord.com/developers/docs/interactions/message-components#button-object-button-styles
 */
enum ButtonStyle: int
{
    case Primary = 1;   // Blurple
    case Secondary = 2; // Grey
    case Success = 3;   // Green
    case Danger = 4;    // Red
    case Link = 5;      // Grey, navigates to URL
}
