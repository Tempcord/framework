<?php

namespace Tempcord\Middleware;

use Tempcord\CommandInteraction;
use Tempest\Log\Logger;

final readonly class ErrorHandlingMiddleware implements CommandMiddleware
{
    public function __construct(
        private Logger $logger,
        private bool $showDetails = false // Show exception details to user (dev mode)
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        try {
            return $next($interaction);
        } catch (\InvalidArgumentException $e) {
            // User input errors - show to user
            $interaction->respond()
                ->warning()
                ->content("Invalid input: {$e->getMessage()}")
                ->send();

            $this->logger->warning("Invalid argument in command", [
                'command' => $interaction->interaction->data->name,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            // Unexpected errors - hide details unless in dev mode
            $message = $this->showDetails
                ? "Error: {$e->getMessage()}\n\nFile: {$e->getFile()}:{$e->getLine()}"
                : 'An unexpected error occurred. Please try again later.';

            $interaction->respond()
                ->error()
                ->content($message)
                ->send();

            $this->logger->error("Command execution error", [
                'command' => $interaction->interaction->data->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
