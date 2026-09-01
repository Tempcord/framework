<?php

namespace Tempcord\Cache;

use CyberWolf\Discord\Parts\Channel;
use CyberWolf\Discord\Parts\Guild;
use CyberWolf\Discord\Parts\GuildMember;
use CyberWolf\Discord\Parts\Role;
use CyberWolf\Discord\Parts\VoiceState;
use Tempest\Container\Singleton;

/**
 * What the gateway has told the bot about the guilds it is in.
 *
 * The Discord library underneath keeps no state of its own, so without this
 * every role check is an HTTP round trip. Reads here are synchronous and never
 * touch the network: a miss returns null rather than fetching, so nothing
 * silently turns into a rate-limited request inside a loop. Fetch it yourself
 * over REST when you need certainty, and hand the result back with a put() so
 * the next read is served from memory.
 */
#[Singleton]
final class Cache
{
    /** @var array<string, GuildState> */
    private array $guilds = [];

    /** @var array<string, string> channel id to the guild it belongs to */
    private array $channelGuilds = [];

    public function guild(string $guildId): ?Guild
    {
        return $this->guilds[$guildId]->guild ?? null;
    }

    /**
     * @return list<Guild>
     */
    public function guilds(): array
    {
        return array_values(array_map(static fn(GuildState $state) => $state->guild, $this->guilds));
    }

    /**
     * Channels are indexed across guilds, so an id on its own is enough — which
     * is how a channel id out of a database or a custom id usually arrives.
     */
    public function channel(string $channelId): ?Channel
    {
        $guildId = $this->channelGuilds[$channelId] ?? null;

        return $guildId === null ? null : ($this->guilds[$guildId]->channels[$channelId] ?? null);
    }

    /**
     * @return list<Channel>
     */
    public function channels(string $guildId): array
    {
        return array_values($this->guilds[$guildId]->channels ?? []);
    }

    public function role(string $guildId, string $roleId): ?Role
    {
        return $this->guilds[$guildId]->roles[$roleId] ?? null;
    }

    /**
     * @return list<Role>
     */
    public function roles(string $guildId): array
    {
        return array_values($this->guilds[$guildId]->roles ?? []);
    }

    public function member(string $guildId, string $userId): ?GuildMember
    {
        return $this->guilds[$guildId]->members[$userId] ?? null;
    }

    /**
     * Every member the gateway has mentioned. Discord only sends the full list
     * for small guilds, so read this as what is known rather than as everyone.
     *
     * @return list<GuildMember>
     */
    public function members(string $guildId): array
    {
        return array_values($this->guilds[$guildId]->members ?? []);
    }

    public function voiceState(string $guildId, string $userId): ?VoiceState
    {
        return $this->guilds[$guildId]->voiceStates[$userId] ?? null;
    }

    /**
     * Who is sitting in the given voice channel.
     *
     * @return list<VoiceState>
     */
    public function voiceStates(string $channelId): array
    {
        $guildId = $this->channelGuilds[$channelId] ?? null;

        if ($guildId === null) {
            return [];
        }

        return array_values(array_filter(
            $this->guilds[$guildId]->voiceStates,
            static fn(VoiceState $state) => $state->channel_id === $channelId,
        ));
    }

    public function putGuild(Guild $guild): void
    {
        $state = $this->guilds[$guild->id] ?? null;

        if ($state === null) {
            $this->guilds[$guild->id] = new GuildState($guild);

            return;
        }

        $state->guild = $guild;
    }

    public function forgetGuild(string $guildId): void
    {
        foreach (array_keys($this->guilds[$guildId]->channels ?? []) as $channelId) {
            unset($this->channelGuilds[$channelId]);
        }

        unset($this->guilds[$guildId]);
    }

    /**
     * A channel outside a guild, or in one the bot has not been told about, is
     * dropped: there is nowhere to file it and nothing that would read it.
     */
    public function putChannel(Channel $channel): void
    {
        if ($channel->guild_id === null) {
            return;
        }

        $state = $this->guilds[$channel->guild_id] ?? null;

        if ($state === null) {
            return;
        }

        $state->channels[$channel->id] = $channel;
        $this->channelGuilds[$channel->id] = $channel->guild_id;
    }

    public function forgetChannel(string $channelId): void
    {
        $guildId = $this->channelGuilds[$channelId] ?? null;

        if ($guildId !== null) {
            unset($this->guilds[$guildId]->channels[$channelId]);
        }

        unset($this->channelGuilds[$channelId]);
    }

    public function putRole(string $guildId, Role $role): void
    {
        $state = $this->guilds[$guildId] ?? null;

        if ($state !== null) {
            $state->roles[$role->id] = $role;
        }
    }

    public function forgetRole(string $guildId, string $roleId): void
    {
        unset($this->guilds[$guildId]->roles[$roleId]);
    }

    /**
     * A member with no user attached cannot be keyed, so it is dropped rather
     * than stored under something made up.
     */
    public function putMember(string $guildId, GuildMember $member): void
    {
        $state = $this->guilds[$guildId] ?? null;

        if ($state !== null && $member->user !== null) {
            $state->members[$member->user->id] = $member;
        }
    }

    public function forgetMember(string $guildId, string $userId): void
    {
        unset($this->guilds[$guildId]->members[$userId]);
    }

    /**
     * A state with no channel means the user left voice altogether, so it is
     * dropped rather than kept as a state pointing nowhere.
     */
    public function putVoiceState(VoiceState $state): void
    {
        if ($state->guild_id === null) {
            return;
        }

        $guild = $this->guilds[$state->guild_id] ?? null;

        if ($guild === null) {
            return;
        }

        if ($state->channel_id === null) {
            unset($guild->voiceStates[$state->user_id]);

            return;
        }

        $guild->voiceStates[$state->user_id] = $state;
    }
}
