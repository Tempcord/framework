<?php

declare(strict_types=1);

namespace Tempcord\Common\Middleware;

use Tempcord\CommandInteraction;
use Tempcord\Middleware\CommandMiddleware;

/**
 * Base middleware for requiring specific roles.
 *
 * Extend this class and override getRoleIds() to specify required roles.
 *
 * @example
 * ```php
 * class ModeratorOnlyMiddleware extends RequireRoleMiddleware {
 *     protected function getRoleIds(): array {
 *         return ['123456789', '987654321']; // Moderator role IDs
 *     }
 * }
 * ```
 */
abstract class RequireRoleMiddleware implements CommandMiddleware
{
    /**
     * Get the role IDs that are allowed to use the command.
     * User must have at least one of these roles.
     *
     * @return array<string>
     */
    abstract protected function getRoleIds(): array;

    /**
     * Get the error message shown when the user doesn't have required roles.
     */
    protected function getErrorMessage(): string
    {
        return 'You do not have the required role to use this command.';
    }

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        $member = $interaction->member;

        if ($member === null) {
            return $interaction->respond()
                ->error()
                ->title('Server Only')
                ->content('This command requires roles and can only be used in a server.')
                ->ephemeral()
                ->send();
        }

        $userRoles = $member->roles ?? [];
        $requiredRoles = $this->getRoleIds();

        // Check if user has at least one required role
        $hasRole = !empty(array_intersect($userRoles, $requiredRoles));

        if (!$hasRole) {
            return $interaction->respond()
                ->error()
                ->title('Missing Role')
                ->content($this->getErrorMessage())
                ->ephemeral()
                ->send();
        }

        return $next($interaction);
    }
}
