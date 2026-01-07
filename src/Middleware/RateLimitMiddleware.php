<?php

namespace Tempcord\Middleware;

use Tempcord\CommandInteraction;

final class RateLimitMiddleware implements CommandMiddleware
{
    /**
     * Storage: [userId => [commandName => [timestamps]]]
     */
    private static array $attempts = [];

    /**
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @param string $scope 'user' or 'guild' - rate limit per user or per guild
     */
    public function __construct(
        private int $maxAttempts = 5,
        private int $decaySeconds = 60,
        private string $scope = 'user'
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        $commandName = $interaction->interaction->data->name;
        $key = $this->getRateLimitKey($interaction);
        $now = time();

        // Initialize storage for this key
        if (!isset(self::$attempts[$key])) {
            self::$attempts[$key] = [];
        }

        if (!isset(self::$attempts[$key][$commandName])) {
            self::$attempts[$key][$commandName] = [];
        }

        // Clean old attempts outside the time window
        self::$attempts[$key][$commandName] = array_filter(
            self::$attempts[$key][$commandName],
            fn($timestamp) => $timestamp > ($now - $this->decaySeconds)
        );

        // Check if rate limit exceeded
        if (count(self::$attempts[$key][$commandName]) >= $this->maxAttempts) {
            $retryAfter = $this->decaySeconds - ($now - min(self::$attempts[$key][$commandName]));

            $interaction->respond()
                ->error()
                ->content("Rate limit exceeded. Please try again in {$retryAfter} seconds.")
                ->send();
            return null;
        }

        // Record this attempt
        self::$attempts[$key][$commandName][] = $now;

        return $next($interaction);
    }

    private function getRateLimitKey(CommandInteraction $interaction): string
    {
        return match ($this->scope) {
            'guild' => $interaction->interaction->guild_id ?? 'dm_' . $interaction->interaction->user->id,
            'user' => $interaction->interaction->user->id,
            default => $interaction->interaction->user->id,
        };
    }
}
