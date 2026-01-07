<?php

declare(strict_types=1);

namespace Tempcord\Enums;

/**
 * Discord Text Input Styles for Modals
 * @see https://discord.com/developers/docs/interactions/message-components#text-input-object-text-input-styles
 */
enum TextInputStyle: int
{
    case Short = 1;     // Single-line input
    case Paragraph = 2; // Multi-line input
}
