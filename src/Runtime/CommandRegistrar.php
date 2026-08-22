<?php

namespace Tempcord\Runtime;

use Discord\Http\Drivers\React;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Tempest\Log\Logger;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\TokenType;
use React\EventLoop\Loop;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\TempcordConfig;
use Throwable;
use function React\Async\await;

/**
 * Pushes the application's commands to Discord.
 *
 * Every command in a scope goes in one request. Discord replaces the whole set,
 * which is what makes a command deleted from the code disappear from Discord
 * rather than linger there indefinitely.
 */
final readonly class CommandRegistrar
{
    /**
     * @param ?Http $http Overrides the client registration would otherwise
     *        build for itself; useful for testing without a network.
     */
    public function __construct(
        private CommandBuilderFactory $builders,
        private TempcordConfig $config,
        private Logger $logger,
        private ?Http $http = null,
    ) {}

    /**
     * @param array<string, CommandDefinition> $commands
     *
     * @return list<Outcome>
     */
    public function register(Discord $discord, array $commands): array
    {
        if ($commands === []) {
            return [Outcome::warning('No commands to register.')];
        }

        try {
            $application = await($discord->rest->application->getCurrent());
        } catch (Throwable $throwable) {
            return [Outcome::error($throwable->getMessage())];
        }

        $http = $this->http ?? $this->buildHttp();
        $outcomes = [];

        foreach ($this->byScope($commands) as $guildId => $scoped) {
            $outcomes[] = $this->overwrite(
                $http,
                $application->id,
                $guildId === '' ? null : (string) $guildId,
                $scoped,
            );
        }

        return $outcomes;
    }

    /**
     * Commands grouped into the sets Discord replaces atomically: the global
     * set, and one set per guild.
     *
     * @param array<string, CommandDefinition> $commands
     *
     * @return array<string, list<CommandDefinition>>
     */
    private function byScope(array $commands): array
    {
        $scopes = [];

        foreach ($commands as $command) {
            $scopes[$command->guildId ?? ''][] = $command;
        }

        return $scopes;
    }

    /**
     * @param list<CommandDefinition> $commands
     */
    private function overwrite(Http $http, string $applicationId, ?string $guildId, array $commands): Outcome
    {
        $scope = $guildId === null ? 'globally' : 'in guild ' . $guildId;

        try {
            await($http->put(
                $guildId === null
                    ? Endpoint::bind(Endpoint::GLOBAL_APPLICATION_COMMANDS, $applicationId)
                    : Endpoint::bind(Endpoint::GUILD_APPLICATION_COMMANDS, $applicationId, $guildId),
                array_map(
                    fn(CommandDefinition $command) => $this->builders->payloadFor($command),
                    $commands,
                ),
            ));
        } catch (Throwable $throwable) {
            return Outcome::error('Registering ' . count($commands) . ' commands ' . $scope . ': ' . $throwable->getMessage());
        }

        return Outcome::success('Registered ' . count($commands) . ' commands ' . $scope . '.');
    }

    /**
     * Fenrir keeps its own HTTP client private and has no bulk overwrite method
     * yet, so registration uses a client of its own. It runs once, before the
     * gateway opens, so the two never compete for a rate limit bucket.
     *
     * @see https://github.com/dc-Ragnarok/Fenrir/pull/133
     */
    private function buildHttp(): Http
    {
        $loop = Loop::get();

        return new Http(
            TokenType::BOT->value . ' ' . $this->config->token,
            $loop,
            $this->logger,
            new React($loop),
        );
    }
}
