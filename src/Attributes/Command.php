<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Enums\EntryPointCommandHandlerType;
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
     * @param list<\Tempcord\Interfaces\Middleware|class-string<\Tempcord\Interfaces\Middleware>> $middleware
     *        run in the order given, outermost first, before any handler this
     *        command has; any of them may answer instead of letting it run
     */
    public function __construct(
        public string|BackedEnum|null $name = null,
        public ?string $description = null,
        int|string|null $guildId = null,
        public bool $isNsfw = false,
        public array $permissions = [],
        public bool $directMessage = true,
        public ApplicationCommandTypes $type = ApplicationCommandTypes::CHAT_INPUT,
        public ?EntryPointCommandHandlerType $handler = null,
        public ?string $translationKey = null,
        public array $middleware = [],
    ) {
        $this->guildId = $guildId === null ? null : (string) $guildId;
    }
}
