<?php

namespace Tempcord\Tests\Unit\Registries;

use PHPUnit\Framework\Attributes\CoversClass;
use CyberWolf\Discord\Bitwise\Bitwise;
use CyberWolf\Discord\Enums\Permission;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\Outcome;
use Tempcord\Runtime\OutcomeLevel;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\GlobalAlphaCommand;
use Tempcord\Tests\Fixtures\GuildAlphaCommand;
use Tempcord\Tests\Fixtures\GuildBetaCommand;
use Tempcord\Tests\Fixtures\OtherGuildAlphaCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Fixtures\RestrictedCommand;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(CommandRegistrar::class)]
final class CommandRegistrationTest extends TestCase
{
    private const string GLOBAL_ENDPOINT = 'applications/424242/commands';

    private RecordingHttp $http;

    protected function setUp(): void
    {
        $this->http = new RecordingHttp();
    }

    private function guildEndpoint(string $guildId): string
    {
        return 'applications/424242/guilds/' . $guildId . '/commands';
    }

    /**
     * @return list<Outcome>
     */
    private function register(string ...$commandClasses): array
    {
        $commands = [];

        foreach ($commandClasses as $class) {
            $definition = $this->definition($class);
            $commands[$definition->key()] = $definition;
        }

        return new CommandRegistrar(
            new CommandBuilderFactory(),
            new TempcordConfig('::token::', new Bitwise()),
            new RecordingLogger(),
            $this->http,
        )->register(new FakeDiscord($this->http), $commands);
    }

    /** @return list<OutcomeLevel> */
    private function levels(array $outcomes): array
    {
        return array_map(static fn(Outcome $outcome) => $outcome->level, $outcomes);
    }

    public function test_global_commands_go_to_the_global_endpoint(): void
    {
        $this->register(PingCommand::class);

        $this->assertSame([self::GLOBAL_ENDPOINT], $this->http->putUrls());
    }

    public function test_a_guild_command_goes_to_the_guild_endpoint(): void
    {
        $this->register(GuildAlphaCommand::class);

        $this->assertSame([$this->guildEndpoint('111')], $this->http->putUrls());
    }

    /**
     * Discord replaces a whole scope at once, so every command in a guild
     * belongs to the same request rather than one request each.
     */
    public function test_commands_in_one_guild_are_sent_as_a_single_set(): void
    {
        $this->register(GuildAlphaCommand::class, GuildBetaCommand::class);

        $this->assertSame([$this->guildEndpoint('111')], $this->http->putUrls());
        $this->assertCount(2, $this->http->puts[0]['content']);
        $this->assertSame(['alpha', 'beta'], array_column($this->http->puts[0]['content'], 'name'));
    }

    public function test_each_guild_gets_its_own_request(): void
    {
        $this->register(GuildAlphaCommand::class, OtherGuildAlphaCommand::class);

        $this->assertSame(
            [$this->guildEndpoint('111'), $this->guildEndpoint('222')],
            $this->http->putUrls(),
        );
    }

    public function test_a_guild_command_does_not_collide_with_the_global_command_of_the_same_name(): void
    {
        $this->register(GuildAlphaCommand::class, GlobalAlphaCommand::class);

        $this->assertSame(
            [$this->guildEndpoint('111'), self::GLOBAL_ENDPOINT],
            $this->http->putUrls(),
        );
    }

    /**
     * The payload is a bare list of command objects; keys would make Discord
     * read it as an object rather than an array.
     */
    public function test_the_payload_is_a_list_of_command_objects(): void
    {
        $this->register(GuildAlphaCommand::class, GuildBetaCommand::class);

        $sent = $this->http->puts[0]['content'];

        $this->assertSame([0, 1], array_keys($sent));
        $this->assertArrayHasKey('description', $sent[0]);
    }

    /**
     * Discord reads default_member_permissions as a decimal bit field. Fenrir's
     * setDefaultMemberPermissions sends the binary representation, so the
     * payload is written directly instead.
     */
    public function test_permissions_are_sent_as_a_decimal_bit_field(): void
    {
        $this->register(RestrictedCommand::class);

        $sent = $this->http->puts[0]['content'][0];

        $this->assertSame(
            (string) (Permission::KICK_MEMBERS->value | Permission::BAN_MEMBERS->value),
            $sent['default_member_permissions'],
        );
    }

    public function test_a_command_without_permissions_is_left_unrestricted(): void
    {
        $this->register(PingCommand::class);

        $this->assertArrayNotHasKey('default_member_permissions', $this->http->puts[0]['content'][0]);
    }

    public function test_it_warns_instead_of_calling_discord_when_there_is_nothing_to_register(): void
    {
        $outcomes = $this->register();

        $this->assertSame([OutcomeLevel::Warning], $this->levels($outcomes));
        $this->assertSame([], $this->http->putUrls());
    }

    public function test_a_failed_application_lookup_stops_before_registering_anything(): void
    {
        $this->http = new RecordingHttp(failApplicationLookup: true);

        $this->assertSame([OutcomeLevel::Error], $this->levels($this->register(PingCommand::class)));
        $this->assertSame([], $this->http->putUrls());
    }

    /**
     * One scope Discord rejects must not stop the others from registering.
     */
    public function test_a_rejected_scope_is_reported_and_the_others_continue(): void
    {
        $this->http = new RecordingHttp(failPostsMatching: ['guilds/111']);

        $outcomes = $this->register(GuildAlphaCommand::class, GlobalAlphaCommand::class);

        $this->assertSame([OutcomeLevel::Error, OutcomeLevel::Success], $this->levels($outcomes));
    }
}
