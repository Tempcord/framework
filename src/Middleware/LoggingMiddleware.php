<?php

namespace Tempcord\Middleware;

use Ragnarok\Fenrir\Enums\InteractionContextType;
use Tempcord\CommandInteraction;
use Tempest\Log\Logger;

final readonly class LoggingMiddleware implements CommandMiddleware
{
    public function __construct(
        private Logger $logger
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        $startTime = microtime(true);
        $contextStartTime = microtime(true);
        $context = $this->buildContext($interaction);
        $contextTime = round((microtime(true) - $contextStartTime) * 1000, 2);

        $this->logger->info("┌─ Command Execution Started ─────────────────────────", [
            'command' => $context['command_path'],
            'user' => $context['user_display'],
            'location' => $context['location'],
            'channel' => $context['channel_id'] ?? 'N/A',
            'options' => $context['options_summary'],
        ]);

        try {
            $commandStartTime = microtime(true);
            $response = $next($interaction);
            $commandTime = round((microtime(true) - $commandStartTime) * 1000, 2);
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info("└─ ✓ Command Completed Successfully ─────────────────", [
                'command' => $context['command_path'],
                'user' => $context['user_display'],
                'execution_time' => "{$totalTime}ms",
                'breakdown' => "context:{$contextTime}ms, command:{$commandTime}ms",
                'response_type' => $response ? get_class($response) : 'void',
            ]);

            return $response;
        } catch (\Throwable $e) {
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->error("└─ ✗ Command Failed ─────────────────────────────────", [
                'command' => $context['command_path'],
                'user' => $context['user_display'],
                'execution_time' => "{$totalTime}ms",
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            throw $e;
        }
    }

    private function buildContext(CommandInteraction $interaction): array
    {
        $data = $interaction->interaction->data;
        $isDM = $interaction->interaction->context === InteractionContextType::BOT_DM;

        // Build user display
        $user = $isDM ? $interaction->interaction->user : $interaction->interaction->member->user;
        $userId = $user->id;
        $username = $user->username;
        $discriminator = $user->discriminator ?? null;
        $userDisplay = $discriminator ? "{$username}#{$discriminator} ({$userId})" : "{$username} ({$userId})";

        // Build command path (command + subcommand if exists)
        $commandPath = $data->name;
        if (isset($data->options[0]->name)) {
            $commandPath .= ' ' . $data->options[0]->name;
        }

        // Build location info
        $guildId = $interaction->interaction->guild_id;
        $location = $guildId ? "Guild:{$guildId}" : "DM";

        // Build options summary
        $optionsSummary = $this->getOptionsSummary($data->options ?? []);

        return [
            'command_path' => $commandPath,
            'user_id' => $userId,
            'user_display' => $userDisplay,
            'location' => $location,
            'guild_id' => $guildId,
            'channel_id' => $interaction->interaction->channel_id ?? null,
            'options_summary' => $optionsSummary,
        ];
    }

    private function getOptionsSummary(array $options): string
    {
        if (empty($options)) {
            return 'none';
        }

        $summary = [];
        foreach ($options as $option) {
            // Skip subcommand/subcommand_group types (they're already in command path)
            if (isset($option->type) && in_array($option->type, [1, 2])) {
                continue;
            }

            if (isset($option->name) && isset($option->value)) {
                $value = is_string($option->value) && strlen($option->value) > 50
                    ? substr($option->value, 0, 47) . '...'
                    : $option->value;
                $summary[] = "{$option->name}={$value}";
            }
        }

        return !empty($summary) ? implode(', ', $summary) : 'none';
    }
}
