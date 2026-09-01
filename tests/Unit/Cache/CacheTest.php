<?php

namespace Tempcord\Tests\Unit\Cache;

use Tempcord\Discord\Parts\GuildMember;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Cache\Cache;
use Tempcord\Cache\GuildState;
use Tempcord\Tests\Doubles\Parts;

#[CoversClass(Cache::class)]
#[CoversClass(GuildState::class)]
final class CacheTest extends BaseTestCase
{
    private Cache $cache;

    protected function setUp(): void
    {
        $this->cache = new Cache();
        $this->cache->putGuild(Parts::guild('g1'));
    }

    public function test_a_miss_is_null_rather_than_an_error(): void
    {
        $cache = new Cache();

        $this->assertNull($cache->guild('nope'));
        $this->assertNull($cache->channel('nope'));
        $this->assertNull($cache->role('nope', 'nope'));
        $this->assertNull($cache->member('nope', 'nope'));
        $this->assertNull($cache->voiceState('nope', 'nope'));
        $this->assertSame([], $cache->guilds());
        $this->assertSame([], $cache->channels('nope'));
        $this->assertSame([], $cache->roles('nope'));
        $this->assertSame([], $cache->members('nope'));
        $this->assertSame([], $cache->voiceStates('nope'));
    }

    public function test_it_holds_a_guild(): void
    {
        $this->assertSame('Guild', $this->cache->guild('g1')?->name);
        $this->assertCount(1, $this->cache->guilds());
    }

    /**
     * A guild is re-sent whenever it changes, and its contents must survive
     * that rather than being dropped along with the old copy.
     */
    public function test_updating_a_guild_keeps_what_was_cached_under_it(): void
    {
        $this->cache->putChannel(Parts::channel('c1', 'g1'));
        $this->cache->putGuild(Parts::guild('g1', 'Renamed'));

        $this->assertSame('Renamed', $this->cache->guild('g1')?->name);
        $this->assertNotNull($this->cache->channel('c1'));
    }

    public function test_a_channel_is_found_by_its_id_alone(): void
    {
        $this->cache->putChannel(Parts::channel('c1', 'g1', 'general'));

        $this->assertSame('general', $this->cache->channel('c1')?->name);
        $this->assertCount(1, $this->cache->channels('g1'));
    }

    public function test_a_channel_outside_a_known_guild_is_not_stored(): void
    {
        $this->cache->putChannel(Parts::channel('dm', null));
        $this->cache->putChannel(Parts::channel('c2', 'unknown-guild'));

        $this->assertNull($this->cache->channel('dm'));
        $this->assertNull($this->cache->channel('c2'));
    }

    public function test_forgetting_a_channel_drops_it_from_both_the_guild_and_the_index(): void
    {
        $this->cache->putChannel(Parts::channel('c1', 'g1'));
        $this->cache->forgetChannel('c1');

        $this->assertNull($this->cache->channel('c1'));
        $this->assertSame([], $this->cache->channels('g1'));
    }

    public function test_forgetting_a_guild_takes_its_channels_out_of_the_index(): void
    {
        $this->cache->putChannel(Parts::channel('c1', 'g1'));
        $this->cache->forgetGuild('g1');

        $this->assertNull($this->cache->guild('g1'));
        $this->assertNull($this->cache->channel('c1'));
    }

    public function test_it_holds_roles_per_guild(): void
    {
        $this->cache->putRole('g1', Parts::role('r1', 'Admin'));

        $this->assertSame('Admin', $this->cache->role('g1', 'r1')?->name);
        $this->assertCount(1, $this->cache->roles('g1'));

        $this->cache->forgetRole('g1', 'r1');

        $this->assertNull($this->cache->role('g1', 'r1'));
    }

    public function test_a_role_for_an_unknown_guild_is_dropped(): void
    {
        $this->cache->putRole('unknown-guild', Parts::role('r1'));

        $this->assertNull($this->cache->role('unknown-guild', 'r1'));
    }

    public function test_it_holds_members_and_the_roles_they_carry(): void
    {
        $this->cache->putMember('g1', Parts::member('u1', ['r1', 'r2']));

        $this->assertSame(['r1', 'r2'], $this->cache->member('g1', 'u1')?->roles);
        $this->assertCount(1, $this->cache->members('g1'));

        $this->cache->forgetMember('g1', 'u1');

        $this->assertNull($this->cache->member('g1', 'u1'));
    }

    public function test_a_member_without_a_user_cannot_be_keyed_and_is_dropped(): void
    {
        $member = new GuildMember();
        $member->user = null;

        $this->cache->putMember('g1', $member);

        $this->assertSame([], $this->cache->members('g1'));
    }

    public function test_it_holds_voice_states_per_channel(): void
    {
        $this->cache->putChannel(Parts::channel('voice', 'g1'));
        $this->cache->putVoiceState(Parts::voiceState('u1', 'voice', 'g1'));
        $this->cache->putVoiceState(Parts::voiceState('u2', 'voice', 'g1'));

        $this->assertCount(2, $this->cache->voiceStates('voice'));
        $this->assertSame('voice', $this->cache->voiceState('g1', 'u1')?->channel_id);
    }

    /**
     * Leaving voice is reported as a state with no channel, which means the
     * user is nowhere rather than that they are in a channel called null.
     */
    public function test_leaving_voice_drops_the_state(): void
    {
        $this->cache->putChannel(Parts::channel('voice', 'g1'));
        $this->cache->putVoiceState(Parts::voiceState('u1', 'voice', 'g1'));
        $this->cache->putVoiceState(Parts::voiceState('u1', null, 'g1'));

        $this->assertNull($this->cache->voiceState('g1', 'u1'));
        $this->assertSame([], $this->cache->voiceStates('voice'));
    }

    public function test_moving_between_channels_leaves_the_old_one(): void
    {
        $this->cache->putChannel(Parts::channel('a', 'g1'));
        $this->cache->putChannel(Parts::channel('b', 'g1'));
        $this->cache->putVoiceState(Parts::voiceState('u1', 'a', 'g1'));
        $this->cache->putVoiceState(Parts::voiceState('u1', 'b', 'g1'));

        $this->assertSame([], $this->cache->voiceStates('a'));
        $this->assertCount(1, $this->cache->voiceStates('b'));
    }

    public function test_a_voice_state_outside_a_known_guild_is_dropped(): void
    {
        $this->cache->putVoiceState(Parts::voiceState('u1', 'c', null));
        $this->cache->putVoiceState(Parts::voiceState('u2', 'c', 'unknown-guild'));

        $this->assertNull($this->cache->voiceState('unknown-guild', 'u2'));
    }
}
