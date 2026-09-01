<?php

namespace Tempcord\Tests\Doubles;

use CyberWolf\Discord\Enums\ChannelType;
use CyberWolf\Discord\Parts\Channel;
use CyberWolf\Discord\Parts\Guild;
use CyberWolf\Discord\Parts\GuildMember;
use CyberWolf\Discord\Parts\Role;
use CyberWolf\Discord\Parts\User;
use CyberWolf\Discord\Parts\VoiceState;

/**
 * The Discord objects the cache stores, built with only the fields under test.
 *
 * The library's parts have typed properties and no constructor, so anything not
 * set here stays uninitialized — which is exactly how a real payload arrives
 * when Discord omits a field.
 */
final class Parts
{
    public static function guild(string $id, string $name = 'Guild'): Guild
    {
        $guild = new Guild();
        $guild->id = $id;
        $guild->name = $name;

        return $guild;
    }

    public static function channel(string $id, ?string $guildId = null, ?string $name = null): Channel
    {
        $channel = new Channel();
        $channel->id = $id;
        $channel->guild_id = $guildId;
        $channel->name = $name;
        $channel->type = ChannelType::GUILD_TEXT;

        return $channel;
    }

    public static function role(string $id, string $name = 'Role'): Role
    {
        $role = new Role();
        $role->id = $id;
        $role->name = $name;

        return $role;
    }

    public static function user(string $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    /**
     * @param list<string> $roles
     */
    public static function member(string $userId, array $roles = [], ?string $nick = null): GuildMember
    {
        $member = new GuildMember();
        $member->user = self::user($userId);
        $member->roles = $roles;
        $member->nick = $nick;

        return $member;
    }

    public static function voiceState(string $userId, ?string $channelId, ?string $guildId = null): VoiceState
    {
        $state = new VoiceState();
        $state->user_id = $userId;
        $state->channel_id = $channelId;
        $state->guild_id = $guildId;

        return $state;
    }
}
