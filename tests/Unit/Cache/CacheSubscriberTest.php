<?php

namespace Tempcord\Tests\Unit\Cache;

use CyberWolf\Discord\Constants\Events;
use CyberWolf\Discord\Gateway\Events\ChannelDelete;
use CyberWolf\Discord\Gateway\Events\GuildCreate;
use CyberWolf\Discord\Gateway\Events\GuildDelete;
use CyberWolf\Discord\Gateway\Events\GuildMemberAdd;
use CyberWolf\Discord\Gateway\Events\GuildMemberRemove;
use CyberWolf\Discord\Gateway\Events\GuildMemberUpdate;
use CyberWolf\Discord\Gateway\Events\GuildMembersChunk;
use CyberWolf\Discord\Gateway\Events\GuildRoleCreate;
use CyberWolf\Discord\Gateway\Events\GuildRoleDelete;
use CyberWolf\Discord\Gateway\Events\GuildUpdate;
use CyberWolf\Discord\Gateway\Events\ThreadCreate;
use CyberWolf\Discord\Gateway\Events\VoiceStateUpdate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use CyberWolf\Discord\Bitwise\Bitwise;
use CyberWolf\Discord\Enums\Intent;
use Tempcord\Cache\Cache;
use Tempcord\Cache\CacheSubscriber;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\Parts;
use Tempcord\Tests\Doubles\RecordingHttp;

#[CoversClass(CacheSubscriber::class)]
final class CacheSubscriberTest extends BaseTestCase
{
    private Cache $cache;
    private FakeDiscord $discord;

    protected function setUp(): void
    {
        $this->cache = new Cache();
        $this->discord = new FakeDiscord(new RecordingHttp());

        $this->subscribe();
    }

    private function subscribe(?TempcordConfig $config = null): void
    {
        new CacheSubscriber(
            $this->cache,
            $config ?? new TempcordConfig(
                token: '::token::',
                intents: Bitwise::from(Intent::GUILDS, Intent::GUILD_MEMBERS),
            ),
        )->subscribe($this->discord);
    }

    private function arrive(string $event, object $payload): void
    {
        $this->discord->gateway->events->emit($event, [$payload]);
    }

    /**
     * @param list<\CyberWolf\Discord\Parts\Channel> $channels
     */
    private function guildCreate(string $id, array $channels = [], array $roles = [], array $members = [], array $voiceStates = [], ?int $memberCount = null): GuildCreate
    {
        $guild = new GuildCreate();
        $guild->id = $id;
        $guild->name = 'Guild';
        $guild->member_count = $memberCount ?? count($members);
        $guild->channels = $channels;
        $guild->roles = $roles;
        $guild->members = $members;
        $guild->voice_states = $voiceStates;

        return $guild;
    }

    public function test_guild_create_fills_everything_it_carries(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate(
            'g1',
            channels: [Parts::channel('c1', name: 'general')],
            roles: [Parts::role('r1', 'Admin')],
            members: [Parts::member('u1', ['r1'])],
            voiceStates: [Parts::voiceState('u1', 'c1')],
        ));

        $this->assertNotNull($this->cache->guild('g1'));
        $this->assertSame('general', $this->cache->channel('c1')?->name);
        $this->assertSame('Admin', $this->cache->role('g1', 'r1')?->name);
        $this->assertSame(['r1'], $this->cache->member('g1', 'u1')?->roles);
        $this->assertCount(1, $this->cache->voiceStates('c1'));
    }

    /**
     * Channels and voice states inside GUILD_CREATE carry no guild id of their
     * own; the guild around them is the answer.
     */
    public function test_it_stamps_the_guild_onto_what_arrives_inside_it(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1', channels: [Parts::channel('c1')]));

        $this->assertSame('g1', $this->cache->channel('c1')?->guild_id);
    }

    public function test_a_guild_with_nothing_in_it_does_not_fall_over(): void
    {
        $guild = new GuildCreate();
        $guild->id = 'g1';
        $guild->name = 'Guild';

        $this->arrive(Events::GUILD_CREATE, $guild);

        $this->assertNotNull($this->cache->guild('g1'));
    }

    public function test_a_renamed_guild_is_updated_in_place(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1', channels: [Parts::channel('c1')]));

        $update = new GuildUpdate();
        $update->id = 'g1';
        $update->name = 'Renamed';
        $this->arrive(Events::GUILD_UPDATE, $update);

        $this->assertSame('Renamed', $this->cache->guild('g1')?->name);
        $this->assertNotNull($this->cache->channel('c1'));
    }

    public function test_leaving_a_guild_forgets_it(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1'));

        $gone = new GuildDelete();
        $gone->id = 'g1';
        $this->arrive(Events::GUILD_DELETE, $gone);

        $this->assertNull($this->cache->guild('g1'));
    }

    public function test_channels_and_threads_are_tracked_as_they_come_and_go(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1'));

        $thread = new ThreadCreate();
        $thread->id = 't1';
        $thread->guild_id = 'g1';
        $thread->name = 'petition';
        $this->arrive(Events::THREAD_CREATE, $thread);

        $this->assertSame('petition', $this->cache->channel('t1')?->name);

        $deleted = new ChannelDelete();
        $deleted->id = 't1';
        $deleted->guild_id = 'g1';
        $this->arrive(Events::CHANNEL_DELETE, $deleted);

        $this->assertNull($this->cache->channel('t1'));
    }

    public function test_roles_are_tracked_as_they_come_and_go(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1'));

        $created = new GuildRoleCreate();
        $created->guild_id = 'g1';
        $created->role = Parts::role('r1', 'Blocked');
        $this->arrive(Events::GUILD_ROLE_CREATE, $created);

        $this->assertSame('Blocked', $this->cache->role('g1', 'r1')?->name);

        $deleted = new GuildRoleDelete();
        $deleted->guild_id = 'g1';
        $deleted->role_id = 'r1';
        $this->arrive(Events::GUILD_ROLE_DELETE, $deleted);

        $this->assertNull($this->cache->role('g1', 'r1'));
    }

    public function test_members_are_tracked_as_they_join_and_leave(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1'));

        $joined = new GuildMemberAdd();
        $joined->guild_id = 'g1';
        $joined->user = Parts::user('u1');
        $joined->roles = ['r1'];
        $this->arrive(Events::GUILD_MEMBER_ADD, $joined);

        $this->assertSame(['r1'], $this->cache->member('g1', 'u1')?->roles);

        $left = new GuildMemberRemove();
        $left->guild_id = 'g1';
        $left->user = Parts::user('u1');
        $this->arrive(Events::GUILD_MEMBER_REMOVE, $left);

        $this->assertNull($this->cache->member('g1', 'u1'));
    }

    /**
     * The event the bot's role handling hangs on: a member's roles change and
     * the cache must show the new set immediately.
     */
    public function test_a_member_update_replaces_the_roles_it_reports(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate(
            'g1',
            members: [Parts::member('u1', ['r1'], nick: 'Vlad')],
        ));

        $update = new GuildMemberUpdate();
        $update->guild_id = 'g1';
        $update->user = Parts::user('u1');
        $update->roles = ['r1', 'r2'];
        $this->arrive(Events::GUILD_MEMBER_UPDATE, $update);

        $this->assertSame(['r1', 'r2'], $this->cache->member('g1', 'u1')?->roles);
    }

    public function test_a_member_update_leaves_fields_it_does_not_carry_alone(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate(
            'g1',
            members: [Parts::member('u1', ['r1'], nick: 'Vlad')],
        ));

        $update = new GuildMemberUpdate();
        $update->guild_id = 'g1';
        $update->user = Parts::user('u1');
        $update->roles = ['r2'];
        $this->arrive(Events::GUILD_MEMBER_UPDATE, $update);

        $this->assertSame('Vlad', $this->cache->member('g1', 'u1')?->nick);
    }

    public function test_an_update_for_a_member_never_seen_before_still_lands(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1'));

        $update = new GuildMemberUpdate();
        $update->guild_id = 'g1';
        $update->user = Parts::user('u1');
        $update->roles = ['r1'];
        $this->arrive(Events::GUILD_MEMBER_UPDATE, $update);

        $this->assertSame(['r1'], $this->cache->member('g1', 'u1')?->roles);
    }

    public function test_a_member_chunk_fills_everyone_it_carries(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1'));

        $chunk = new GuildMembersChunk();
        $chunk->guild_id = 'g1';
        $chunk->members = [Parts::member('u1'), Parts::member('u2')];
        $this->arrive(Events::GUILD_MEMBERS_CHUNK, $chunk);

        $this->assertCount(2, $this->cache->members('g1'));
    }

    public function test_a_voice_state_update_moves_the_user(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate(
            'g1',
            channels: [Parts::channel('a'), Parts::channel('b')],
            voiceStates: [Parts::voiceState('u1', 'a')],
        ));

        $moved = new VoiceStateUpdate();
        $moved->guild_id = 'g1';
        $moved->user_id = 'u1';
        $moved->channel_id = 'b';
        $this->arrive(Events::VOICE_STATE_UPDATE, $moved);

        $this->assertSame([], $this->cache->voiceStates('a'));
        $this->assertCount(1, $this->cache->voiceStates('b'));
    }

    /**
     * Discord only sends the whole member list for a small guild. Without
     * asking for the rest the cache quietly holds a fraction of a busy server.
     */
    public function test_it_asks_for_the_members_a_large_guild_left_out(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate(
            'g1',
            members: [Parts::member('u1')],
            memberCount: 5000,
        ));

        $requests = $this->discord->memberRequests();

        $this->assertCount(1, $requests);
        $this->assertSame(
            ['guild_id' => 'g1', 'query' => '', 'limit' => 0],
            $requests[0]->get(),
        );
    }

    public function test_a_guild_that_arrived_whole_is_not_asked_for_again(): void
    {
        $this->arrive(Events::GUILD_CREATE, $this->guildCreate(
            'g1',
            members: [Parts::member('u1'), Parts::member('u2')],
        ));

        $this->assertSame([], $this->discord->memberRequests());
    }

    /**
     * Asking without the intent is answered with nothing, so the round trip is
     * not worth making.
     */
    public function test_it_does_not_ask_without_the_members_intent(): void
    {
        $this->discord = new FakeDiscord(new RecordingHttp());
        $this->subscribe(new TempcordConfig('::token::', Bitwise::from(Intent::GUILDS)));

        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1', memberCount: 5000));

        $this->assertSame([], $this->discord->memberRequests());
    }

    public function test_chunking_can_be_switched_off(): void
    {
        $this->discord = new FakeDiscord(new RecordingHttp());
        $this->subscribe(new TempcordConfig(
            token: '::token::',
            intents: Bitwise::from(Intent::GUILDS, Intent::GUILD_MEMBERS),
            chunkMembers: false,
        ));

        $this->arrive(Events::GUILD_CREATE, $this->guildCreate('g1', memberCount: 5000));

        $this->assertSame([], $this->discord->memberRequests());
    }
}
