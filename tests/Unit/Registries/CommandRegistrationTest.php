<?php

namespace Tempcord\Tests\Unit\Registries;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Runtime\Outcome;
use Tempcord\Runtime\OutcomeLevel;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\GlobalAlphaCommand;
use Tempcord\Tests\Fixtures\GuildAlphaCommand;
use Tempcord\Tests\Fixtures\GuildBetaCommand;
use Tempcord\Tests\Fixtures\OtherGuildAlphaCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;

#[CoversClass(CommandsRegistry::class)]
final class CommandRegistrationTest extends TestCase
{
    private const string GLOBAL_ENDPOINT = 'applications/424242/commands';

    private function guildEndpoint(string $guildId): string
    {
        return 'applications/424242/guilds/' . $guildId . '/commands';
    }

    private function registry(string ...$commandClasses): CommandsRegistry
    {
        $discord = new FakeDiscord(new RecordingHttp());

        $registry = new CommandsRegistry(
            extension: new AllCommandExtension(),
            builders: new CommandBuilderFactory(),
            dispatcher: new CommandDispatcher(
                new ArgumentResolver(new OptionValueResolver($discord)),
                new GenericContainer(),
                new NullLogger(),
            ),
            autocomplete: new AutocompleteResponder(new ChoiceFactory()),
        );

        foreach ($commandClasses as $class) {
            $registry->add($this->definition($class));
        }

        return $registry;
    }

    /** @return list<OutcomeLevel> */
    private function levels(array $outcomes): array
    {
        return array_map(static fn(Outcome $outcome) => $outcome->level, $outcomes);
    }

    public function test_a_global_command_goes_to_the_global_endpoint(): void
    {
        $http = new RecordingHttp();

        $this->registry(PingCommand::class)->register(new FakeDiscord($http));

        $this->assertSame([self::GLOBAL_ENDPOINT], $http->postedUrls());
    }

    public function test_a_guild_command_goes_to_the_guild_endpoint(): void
    {
        $http = new RecordingHttp();

        $this->registry(GuildAlphaCommand::class)->register(new FakeDiscord($http));

        $this->assertSame([$this->guildEndpoint('111')], $http->postedUrls());
    }

    /**
     * The original defect: guild commands were keyed by guild id alone, so a
     * second command in the same guild silently replaced the first.
     */
    public function test_two_commands_in_the_same_guild_are_both_registered(): void
    {
        $http = new RecordingHttp();

        $this->registry(GuildAlphaCommand::class, GuildBetaCommand::class)
            ->register(new FakeDiscord($http));

        $this->assertSame(
            [$this->guildEndpoint('111'), $this->guildEndpoint('111')],
            $http->postedUrls(),
        );
    }

    public function test_the_same_command_name_in_two_guilds_is_registered_in_each(): void
    {
        $http = new RecordingHttp();

        $this->registry(GuildAlphaCommand::class, OtherGuildAlphaCommand::class)
            ->register(new FakeDiscord($http));

        $this->assertSame(
            [$this->guildEndpoint('111'), $this->guildEndpoint('222')],
            $http->postedUrls(),
        );
    }

    public function test_a_guild_command_does_not_collide_with_the_global_command_of_the_same_name(): void
    {
        $http = new RecordingHttp();

        $this->registry(GuildAlphaCommand::class, GlobalAlphaCommand::class)
            ->register(new FakeDiscord($http));

        $this->assertSame(
            [$this->guildEndpoint('111'), self::GLOBAL_ENDPOINT],
            $http->postedUrls(),
        );
    }

    public function test_it_warns_instead_of_calling_discord_when_there_is_nothing_to_register(): void
    {
        $http = new RecordingHttp();

        $outcomes = $this->registry()->register(new FakeDiscord($http));

        $this->assertSame([OutcomeLevel::Warning], $this->levels($outcomes));
        $this->assertSame('No commands to register.', $outcomes[0]->message);
        $this->assertSame([], $http->postedUrls());
    }

    public function test_a_failed_application_lookup_stops_before_registering_anything(): void
    {
        $http = new RecordingHttp(failApplicationLookup: true);

        $outcomes = $this->registry(PingCommand::class)->register(new FakeDiscord($http));

        $this->assertSame([OutcomeLevel::Error], $this->levels($outcomes));
        $this->assertSame([], $http->postedUrls());
    }

    /**
     * One command Discord rejects must not stop the rest from registering.
     */
    public function test_a_rejected_command_is_reported_and_the_others_continue(): void
    {
        $http = new RecordingHttp(failPostsMatching: ['guilds/111']);

        $outcomes = $this->registry(GuildAlphaCommand::class, GlobalAlphaCommand::class)
            ->register(new FakeDiscord($http));

        $this->assertSame([OutcomeLevel::Error, OutcomeLevel::Success], $this->levels($outcomes));
        $this->assertStringContainsString('Command "alpha":', $outcomes[0]->message);
        $this->assertSame('Command "alpha" registered globally.', $outcomes[1]->message);
    }
}
