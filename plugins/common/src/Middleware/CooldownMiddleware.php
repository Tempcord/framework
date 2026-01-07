<?php

declare(strict_types=1);

namespace Tempcord\Common\Middleware;

use Tempcord\CommandInteraction;
use Tempcord\Common\CommonConfig;
use Tempcord\Middleware\CommandMiddleware;

/**
 * Middleware that enforces a cooldown between command uses.
 *
 * Prevents command spam by requiring users to wait between uses.
 * Default cooldown is configured in CommonConfig.
 *
 * For custom cooldowns, create a subclass:
 * ```php
 * class LongCooldownMiddleware extends CooldownMiddleware {
 *     protected function getCooldownSeconds(): int { return 60; }
 * }
 * ```
 */
class CooldownMiddleware implements CommandMiddleware
{
    /** @var array<string, float> Command usage timestamps */
    private static array $cooldowns = [];

    public function __construct(
        private readonly CommonConfig $config,
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        $userId = $interaction->member?->user?->id ?? $interaction->user?->id;
        $commandName = $interaction->data?->name ?? 'unknown';

        if ($userId === null) {
            return $next($interaction);
        }

        $key = "{$userId}:{$commandName}";
        $cooldownSeconds = $this->getCooldownSeconds();
        $now = microtime(true);

        if (isset(self::$cooldowns[$key])) {
            $elapsed = $now - self::$cooldowns[$key];
            $remaining = $cooldownSeconds - $elapsed;

            if ($remaining > 0) {
                return $interaction->respond()
                    ->warning()
                    ->title('⏳ Cooldown')
                    ->content(sprintf(
                        'Please wait **%.1f seconds** before using this command again.',
                        $remaining
                    ))
                    ->ephemeral()
                    ->send();
            }
        }

        self::$cooldowns[$key] = $now;

        // Clean up old entries periodically
        $this->cleanup();

        return $next($interaction);
    }

    /**
     * Get cooldown duration in seconds.
     * Override this method in subclasses for custom cooldowns.
     */
    protected function getCooldownSeconds(): int
    {
        return $this->config->defaultCooldown;
    }

    /**
     * Clean up expired cooldown entries to prevent memory leaks.
     */
    private function cleanup(): void
    {
        // Only cleanup occasionally (1% chance per request)
        if (mt_rand(1, 100) !== 1) {
            return;
        }

        $now = microtime(true);
        $maxAge = 3600; // Keep entries for 1 hour max

        foreach (self::$cooldowns as $key => $timestamp) {
            if ($now - $timestamp > $maxAge) {
                unset(self::$cooldowns[$key]);
            }
        }
    }

    /**
     * Reset cooldown for a specific user/command.
     */
    public static function resetCooldown(string $userId, string $commandName): void
    {
        unset(self::$cooldowns["{$userId}:{$commandName}"]);
    }

    /**
     * Reset all cooldowns (useful for testing).
     */
    public static function resetAllCooldowns(): void
    {
        self::$cooldowns = [];
    }
}
