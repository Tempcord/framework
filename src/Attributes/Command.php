<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;

/**
 * Declares a class as a Discord application command.
 *
 * This is a plain declaration and nothing more. Everything it implies — the
 * command's name when none is given, its options, the methods that handle it —
 * is worked out by the CommandCompiler at discovery time.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class Command
{
    /**
     * The guild this command is scoped to, or null when it is registered globally.
     */
    public ?string $guildId;

    /**
     * @param string|BackedEnum|null $name defaults to the class name, with a
     *        Command prefix or suffix stripped and the rest snake_cased
     * @param list<string> $permissions
     */
    public function __construct(
        public string|BackedEnum|null $name = null,
        public ?string $description = null,
        int|string|null $guildId = null,
        public bool $isNsfw = false,
        public array $permissions = [],
        public bool $directMessage = true,
        public ApplicationCommandTypes $type = ApplicationCommandTypes::CHAT_INPUT,
    ) {
        $this->guildId = $guildId === null ? null : (string) $guildId;
    }
}
