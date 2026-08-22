<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Tempcord\AllCommandExtension;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\GlobalAlphaCommand;
use Tempcord\Tests\Fixtures\GuildAlphaCommand;
use Tempcord\Tests\Fixtures\GuildBetaCommand;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\OtherGuildAlphaCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempest\Console\Console;

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
        $registry = new CommandsRegistry(new AllCommandExtension());

        foreach ($commandClasses as $class) {
            $registry->add($this->command($class));
        }

        return $registry;
    }

    /** @return array<string, mixed> */
    private function storedCommands(CommandsRegistry $registry): array
    {
        return new ReflectionProperty(CommandsRegistry::class, 'commands')->getValue($registry);
    }

    public function test_a_global_command_goes_to_the_global_endpoint(): void
    {
        $http = new RecordingHttp();

        $this->registry(PingCommand::class)
            ->register($this->createStub(Console::class), new FakeDiscord($http));

        $this->assertSame([self::GLOBAL_ENDPOINT], $http->postedUrls());
    }

    public function test_a_guild_command_goes_to_the_guild_endpoint(): void
    {
        $http = new RecordingHttp();

        $this->registry(GuildAlphaCommand::class)
            ->register($this->createStub(Console::class), new FakeDiscord($http));

        $this->assertSame([$this->guildEndpoint('111')], $http->postedUrls());
    }

    /**
     * The original defect: guild commands were keyed by guild id alone, so a
     * second command in the same guild silently replaced the first.
     */
    public function test_two_commands_in_the_same_guild_are_both_registered(): void
    {
        $http = new RecordingHttp();
        $registry = $this->registry(GuildAlphaCommand::class, GuildBetaCommand::class);

        $this->assertCount(2, $this->storedCommands($registry));

        $registry->register($this->createStub(Console::class), new FakeDiscord($http));

        $this->assertSame(
            [$this->guildEndpoint('111'), $this->guildEndpoint('111')],
            $http->postedUrls(),
        );
    }

    public function test_the_same_command_name_in_two_guilds_is_registered_in_each(): void
    {
        $http = new RecordingHttp();

        $this->registry(GuildAlphaCommand::class, OtherGuildAlphaCommand::class)
            ->register($this->createStub(Console::class), new FakeDiscord($http));

        $this->assertSame(
            [$this->guildEndpoint('111'), $this->guildEndpoint('222')],
            $http->postedUrls(),
        );
    }

    public function test_a_guild_command_does_not_displace_the_global_command_of_the_same_name(): void
    {
        $http = new RecordingHttp();

        $this->registry(GlobalAlphaCommand::class, GuildAlphaCommand::class)
            ->register($this->createStub(Console::class), new FakeDiscord($http));

        $this->assertSame(
            [self::GLOBAL_ENDPOINT, $this->guildEndpoint('111')],
            $http->postedUrls(),
        );
    }

    public function test_a_mixed_set_is_routed_per_command(): void
    {
        $http = new RecordingHttp();

        $this->registry(PingCommand::class, GuildAlphaCommand::class, ModerationCommand::class)
            ->register($this->createStub(Console::class), new FakeDiscord($http));

        $this->assertSame(
            [self::GLOBAL_ENDPOINT, $this->guildEndpoint('111'), self::GLOBAL_ENDPOINT],
            $http->postedUrls(),
        );
    }

    public function test_the_guild_id_is_reported_on_success(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('success')
            ->with('Command "alpha" registered in guild 111.');

        $this->registry(GuildAlphaCommand::class)->register($console, new FakeDiscord(new RecordingHttp()));
    }

    public function test_one_failing_command_does_not_stop_the_others(): void
    {
        $http = new RecordingHttp(failPostsMatching: ['/guilds/']);
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Command "alpha"'));
        $console->expects($this->exactly(2))->method('success');

        $this->registry(PingCommand::class, GuildAlphaCommand::class, ModerationCommand::class)
            ->register($console, new FakeDiscord($http));

        $this->assertCount(3, $http->postedUrls());
    }

    public function test_nothing_is_registered_when_the_application_lookup_fails(): void
    {
        $http = new RecordingHttp(failApplicationLookup: true);
        $console = $this->createMock(Console::class);
        $console->expects($this->once())->method('error');
        $console->expects($this->never())->method('success');

        $this->registry(PingCommand::class)->register($console, new FakeDiscord($http));

        $this->assertSame([], $http->postedUrls());
    }
}
