<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Enums\Permission;

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
     * @param list<Permission> $permissions the permissions a member needs by
     *        default; an empty list leaves the command unrestricted
     * @param string|null $translationKey the catalog key this command's
     *        translations live under. Keys for everything beneath it are
     *        derived from position, so "commands.music" gives
     *        commands.music.description for the command,
     *        commands.music.playlist.play.description for a subcommand, and
     *        commands.music.playlist.play.title.description for its option.
     */
    public function __construct(
        public string|BackedEnum|null $name = null,
        public ?string $description = null,
        int|string|null $guildId = null,
        public bool $isNsfw = false,
        public array $permissions = [],
        public bool $directMessage = true,
        public ApplicationCommandTypes $type = ApplicationCommandTypes::CHAT_INPUT,
        public ?string $translationKey = null,
    ) {
        $this->guildId = $guildId === null ? null : (string) $guildId;
    }
}
