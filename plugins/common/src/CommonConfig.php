<?php

declare(strict_types=1);

namespace Tempcord\Common;

/**
 * Configuration for the common plugin.
 */
class CommonConfig
{
    public function __construct(
        /**
         * Bot owner user ID(s) for owner-only commands.
         * @var array<string>
         */
        public array $ownerIds = [],

        /**
         * Whether maintenance mode is enabled.
         */
        public bool $maintenanceMode = false,

        /**
         * Message shown to users during maintenance mode.
         */
        public string $maintenanceMessage = 'The bot is currently undergoing maintenance. Please try again later.',

        /**
         * User IDs allowed to bypass maintenance mode.
         * @var array<string>
         */
        public array $maintenanceBypass = [],

        /**
         * Default cooldown in seconds for CooldownMiddleware.
         */
        public int $defaultCooldown = 3,

        /**
         * Discord activity statuses for rotation.
         * @var array<array{type: int, name: string}>
         */
        public array $statusRotation = [],

        /**
         * Interval in seconds between status rotations.
         */
        public int $statusRotationInterval = 300,
    ) {}

    /**
     * Check if a user ID is a bot owner.
     */
    public function isOwner(string $userId): bool
    {
        return in_array($userId, $this->ownerIds, true);
    }

    /**
     * Check if a user can bypass maintenance mode.
     */
    public function canBypassMaintenance(string $userId): bool
    {
        return $this->isOwner($userId) || in_array($userId, $this->maintenanceBypass, true);
    }

    /**
     * Add owner IDs.
     */
    public function withOwners(array $ownerIds): self
    {
        $clone = clone $this;
        $clone->ownerIds = $ownerIds;
        return $clone;
    }

    /**
     * Enable maintenance mode.
     */
    public function withMaintenance(bool $enabled = true, ?string $message = null): self
    {
        $clone = clone $this;
        $clone->maintenanceMode = $enabled;
        if ($message !== null) {
            $clone->maintenanceMessage = $message;
        }
        return $clone;
    }

    /**
     * Set status rotation.
     */
    public function withStatusRotation(array $statuses, int $interval = 300): self
    {
        $clone = clone $this;
        $clone->statusRotation = $statuses;
        $clone->statusRotationInterval = $interval;
        return $clone;
    }
}
