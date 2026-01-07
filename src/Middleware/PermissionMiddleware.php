<?php

namespace Tempcord\Middleware;

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Tempcord\CommandInteraction;

final readonly class PermissionMiddleware implements CommandMiddleware
{
    /**
     * @param Bitwise $requiredPermissions Discord permissions bitfield
     * @param string $errorMessage Custom error message
     */
    public function __construct(
        private Bitwise $requiredPermissions,
        private string $errorMessage = 'You do not have permission to use this command.'
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        // Get user's member information
        $member = $interaction->interaction->member;

        // If in DM, reject if permissions are required
        if ($member === null) {
            $interaction->respond()
                ->error()
                ->content('This command can only be used in a server.')
                ->send();
            return null;
        }

        // Parse user permissions from app_permissions field
        $userPermissions = $interaction->interaction->app_permissions
            ? new Bitwise($interaction->interaction->app_permissions)
            : new Bitwise('0');

        // Check if user has all required permissions
        if (!$userPermissions->has($this->requiredPermissions)) {
            $interaction->respond()
                ->error()
                ->content($this->errorMessage)
                ->send();
            return null;
        }

        return $next($interaction);
    }
}
