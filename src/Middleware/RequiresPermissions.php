<?php

namespace Tempcord\Middleware;

use Tempcord\Discord\Enums\Permission;
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Interfaces\Middleware;

/**
 * Refuses anyone whose permissions in the channel fall short.
 *
 * #[Command(permissions: ...)] already asks Discord to hide the command, and
 * for most commands that is enough. It is a default, though: a guild
 * administrator can override it in Server Settings, and it says nothing at all
 * about a subcommand — Discord scopes permissions to the whole command, so a
 * command with one subcommand for everybody and another for moderators cannot
 * be described that way at all. This checks at the moment of use, where the
 * answer is not a suggestion.
 *
 *     #[Subcommand(
 *         name: 'panel',
 *         description: 'Publishes the panel.',
 *         middleware: [new RequiresPermissions([Permission::MANAGE_GUILD])],
 *     )]
 *
 * Discord sends the caller's permissions already computed for the channel the
 * command was used in, so no roles have to be read back and nothing has to be
 * cached.
 */
final readonly class RequiresPermissions implements Middleware
{
    /**
     * @param list<Permission> $permissions every one of which the caller must
     *        hold; an administrator holds all of them by definition
     * @param string $refusal what the caller is told, ephemerally
     */
    public function __construct(
        public array $permissions,
        public string $refusal = 'You are not allowed to use this command.',
    ) {}

    public function __invoke(
        CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction,
        callable $next,
    ): void
    {
        if (!$this->allows($interaction)) {
            $interaction->reply($this->refusal, ephemeral: true);

            return;
        }

        $next($interaction);
    }

    private function allows(
        CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction,
    ): bool
    {
        $held = $interaction->interaction->member?->permissions;

        /*
         * No member means the command was used somewhere there are no guild
         * permissions to hold — a direct message, most often. Nobody clears a
         * permission check there.
         */
        if ($held === null) {
            return false;
        }

        $held = (int) $held;

        if (($held & Permission::ADMINISTRATOR->value) === Permission::ADMINISTRATOR->value) {
            return true;
        }

        foreach ($this->permissions as $permission) {
            if (($held & $permission->value) !== $permission->value) {
                return false;
            }
        }

        return true;
    }
}
