<?php

namespace Tempcord\Cache;

use Tempcord\Discord\Constants\Events;
use Tempcord\Discord\Discord;
use Tempcord\Discord\Gateway\Events\ChannelCreate;
use Tempcord\Discord\Gateway\Events\ChannelDelete;
use Tempcord\Discord\Gateway\Events\ChannelUpdate;
use Tempcord\Discord\Gateway\Events\GuildCreate;
use Tempcord\Discord\Gateway\Events\GuildDelete;
use Tempcord\Discord\Gateway\Events\GuildMemberAdd;
use Tempcord\Discord\Gateway\Events\GuildMemberRemove;
use Tempcord\Discord\Gateway\Events\GuildMemberUpdate;
use Tempcord\Discord\Gateway\Events\GuildMembersChunk;
use Tempcord\Discord\Gateway\Events\GuildRoleCreate;
use Tempcord\Discord\Gateway\Events\GuildRoleDelete;
use Tempcord\Discord\Gateway\Events\GuildRoleUpdate;
use Tempcord\Discord\Gateway\Events\GuildUpdate;
use Tempcord\Discord\Gateway\Events\ThreadCreate;
use Tempcord\Discord\Gateway\Events\ThreadDelete;
use Tempcord\Discord\Gateway\Events\ThreadUpdate;
use Tempcord\Discord\Gateway\Events\VoiceStateUpdate;
use Tempcord\Discord\Gateway\Helpers\RequestGuildMembersBuilder;
use Tempcord\Discord\Enums\Intent;
use Tempcord\Discord\Parts\GuildMember;
use Tempcord\TempcordConfig;

/**
 * Keeps the cache in step with the gateway.
 *
 * Attached before any listener the bot declares, so a handler reading the cache
 * sees the state the event it is handling has already produced.
 */
final class CacheSubscriber
{
    private Discord $discord;

    /**
     * Fields a member update carries. Copied onto the member already held so
     * the parts of it Discord left out of the update survive.
     */
    private const array MEMBER_FIELDS = [
        'nick', 'avatar', 'roles', 'joined_at', 'premium_since',
        'deaf', 'mute', 'pending', 'communication_disabled_until',
    ];

    public function __construct(
        private readonly Cache $cache,
        private readonly TempcordConfig $config,
    ) {}

    public function subscribe(Discord $discord): void
    {
        $this->discord = $discord;
        $events = $discord->gateway->events;

        $events->on(Events::GUILD_CREATE, $this->onGuildCreate(...));
        $events->on(Events::GUILD_UPDATE, fn(GuildUpdate $guild) => $this->cache->putGuild($guild));
        $events->on(Events::GUILD_DELETE, fn(GuildDelete $guild) => $this->cache->forgetGuild($guild->id));

        $events->on(Events::CHANNEL_CREATE, fn(ChannelCreate $channel) => $this->cache->putChannel($channel));
        $events->on(Events::CHANNEL_UPDATE, fn(ChannelUpdate $channel) => $this->cache->putChannel($channel));
        $events->on(Events::CHANNEL_DELETE, fn(ChannelDelete $channel) => $this->cache->forgetChannel($channel->id));

        $events->on(Events::THREAD_CREATE, fn(ThreadCreate $thread) => $this->cache->putChannel($thread));
        $events->on(Events::THREAD_UPDATE, $this->onThreadUpdate(...));
        $events->on(Events::THREAD_DELETE, fn(ThreadDelete $thread) => $this->cache->forgetChannel($thread->id));

        $events->on(Events::GUILD_ROLE_CREATE, fn(GuildRoleCreate $e) => $this->cache->putRole($e->guild_id, $e->role));
        $events->on(Events::GUILD_ROLE_UPDATE, fn(GuildRoleUpdate $e) => $this->cache->putRole($e->guild_id, $e->role));
        $events->on(Events::GUILD_ROLE_DELETE, fn(GuildRoleDelete $e) => $this->cache->forgetRole($e->guild_id, $e->role_id));

        $events->on(Events::GUILD_MEMBER_ADD, fn(GuildMemberAdd $m) => $this->cache->putMember($m->guild_id, $m));
        $events->on(Events::GUILD_MEMBER_UPDATE, $this->onMemberUpdate(...));
        $events->on(Events::GUILD_MEMBER_REMOVE, fn(GuildMemberRemove $m) => $this->cache->forgetMember($m->guild_id, $m->user->id));
        $events->on(Events::GUILD_MEMBERS_CHUNK, $this->onMembersChunk(...));

        $events->on(Events::VOICE_STATE_UPDATE, fn(VoiceStateUpdate $state) => $this->cache->putVoiceState($state));
    }

    private function onGuildCreate(GuildCreate $guild): void
    {
        $this->cache->putGuild($guild);

        foreach ($guild->roles ?? [] as $role) {
            $this->cache->putRole($guild->id, $role);
        }

        foreach ([...($guild->channels ?? []), ...($guild->threads ?? [])] as $channel) {
            /*
             * Channels inside GUILD_CREATE arrive without a guild_id, since the
             * guild they belong to is the one carrying them.
             */
            $channel->guild_id ??= $guild->id;

            $this->cache->putChannel($channel);
        }

        foreach ($guild->members ?? [] as $member) {
            $this->cache->putMember($guild->id, $member);
        }

        foreach ($guild->voice_states ?? [] as $state) {
            $state->guild_id ??= $guild->id;

            $this->cache->putVoiceState($state);
        }

        $this->chunkMembers($guild);
    }

    /**
     * Asks for the members GUILD_CREATE left out.
     *
     * Discord only sends the whole member list for a small guild; past that it
     * sends a slice and expects to be asked for the rest, which arrives as
     * GUILD_MEMBERS_CHUNK events the subscriber above already stores. Without
     * this the member cache silently becomes a fraction of a busy server.
     */
    private function chunkMembers(GuildCreate $guild): void
    {
        if (!$this->config->chunkMembers) {
            return;
        }

        /*
         * Asking without the intent is answered with nothing, so it is only
         * worth the round trip when the bot could actually receive members.
         */
        if (!$this->config->intents->has(Intent::GUILD_MEMBERS->value)) {
            return;
        }

        $received = count($guild->members ?? []);
        $total = $guild->member_count ?? $received;

        if ($received >= $total) {
            return;
        }

        $this->discord->gateway->requestGuildMembers(
            RequestGuildMembersBuilder::everyone($guild->id),
        );
    }

    /**
     * The library models THREAD_UPDATE as a list of threads rather than as the
     * single channel Discord documents, so every thread it carries is stored.
     */
    private function onThreadUpdate(ThreadUpdate $update): void
    {
        foreach ($update->threads ?? [] as $thread) {
            $thread->guild_id ??= $update->guild_id;

            $this->cache->putChannel($thread);
        }
    }

    private function onMembersChunk(GuildMembersChunk $chunk): void
    {
        foreach ($chunk->members as $member) {
            $this->cache->putMember($chunk->guild_id, $member);
        }
    }

    /**
     * A member update is not a member: it is the fields that changed. Merging
     * it onto what is held keeps the rest — flags, permissions — rather than
     * replacing a whole member with a partial one.
     */
    private function onMemberUpdate(GuildMemberUpdate $update): void
    {
        $member = $this->cache->member($update->guild_id, $update->user->id) ?? new GuildMember();
        $member->user = $update->user;

        foreach (self::MEMBER_FIELDS as $field) {
            if (isset($update->{$field})) {
                $member->{$field} = $update->{$field};
            }
        }

        $this->cache->putMember($update->guild_id, $member);
    }
}
