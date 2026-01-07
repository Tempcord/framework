<?php

namespace Tempcord\Middleware;

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\Permission;
use Tempcord\CommandInteraction;

final readonly class AdminMiddleware implements CommandMiddleware
{
    public function __construct(
        private string $errorMessage = 'This command requires administrator permissions.'
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        $member = $interaction->interaction->member;

        // Check if a command is used in a guild
        if ($member === null) {
            $interaction->respond()
                ->error()
                ->content('This command can only be used in a server.')
                ->send();
            return null;
        }

        // Check app_permissions for ADMINISTRATOR permission
        $userPermissions = $interaction->interaction->app_permissions
            ? new Bitwise($interaction->interaction->app_permissions)
            : new Bitwise('0');

        $adminPermission = new Bitwise(Permission::ADMINISTRATOR->value);

        if (!$userPermissions->has($adminPermission->getBitSet())) {
            $interaction->respond()
                ->error()
                ->content($this->errorMessage)
                ->send();
            return null;
        }

        return $next($interaction);
    }
}
