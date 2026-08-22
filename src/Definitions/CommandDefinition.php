<?php

namespace Tempcord\Definitions;

use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;

/**
 * A fully resolved command: everything the framework needs to register it with
 * Discord and to dispatch the interactions that come back, worked out once at
 * discovery time rather than on every access.
 */
final readonly class CommandDefinition
{
    /**
     * @param array<string, SubcommandGroupDefinition|SubcommandDefinition|OptionDefinition> $options
     *        the command's direct children, in the shape Discord expects
     * @param array<string, HandlerDefinition> $handlers keyed by dotted interaction path
     * @param list<string> $permissions
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $guildId,
        public bool $isNsfw,
        public bool $directMessage,
        public ApplicationCommandTypes $type,
        public array $permissions,
        public array $options,
        public array $handlers,
    ) {}

    public function isGlobal(): bool
    {
        return $this->guildId === null;
    }

    /**
     * Commands are keyed by name and scoped by guild, so a guild command
     * collides neither with another command in the same guild nor with the
     * global command of the same name.
     *
     * Discord command names cannot contain ":", so the two key shapes can
     * never overlap.
     */
    public function key(): string
    {
        return $this->guildId === null
            ? $this->name
            : $this->guildId . ':' . $this->name;
    }

    /**
     * Folds another declaration of the same command into this one, so a command
     * may be split across several classes. Options and handlers from both sides
     * survive; everything else is taken from the earlier declaration.
     */
    public function mergedWith(self $other): self
    {
        return new self(
            name: $this->name,
            description: $this->description ?? $other->description,
            guildId: $this->guildId,
            isNsfw: $this->isNsfw,
            directMessage: $this->directMessage,
            type: $this->type,
            permissions: $this->permissions,
            options: [...$this->options, ...$other->options],
            handlers: [...$this->handlers, ...$other->handlers],
        );
    }
}
