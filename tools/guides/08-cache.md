# Cache

The Discord library underneath keeps no state of its own, so without a cache every
"does this member have that role" is an HTTP round trip. Tempcord keeps what the gateway
has already told the bot, and hands it back synchronously.

## Reading

Ask the container for the cache and read from it:

```php
use Tempcord\Cache\Cache;
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Command;

#[Command(description: 'Is this member blocked?')]
final readonly class BlockedCommand
{
    public function __construct(private Cache $cache) {}

    public function __invoke(CommandInteraction $interaction): void
    {
        $member = $this->cache->member($guildId, $userId);
        $blocked = in_array($blockedRoleId, $member?->roles ?? [], true);
    }
}
```

Channels are indexed across guilds, so a channel id on its own is enough — which is how
one usually arrives, out of a database row or a custom id:

```php
$this->cache->channel($channelId);
$this->cache->voiceStates($voiceChannelId);   // who is sitting in it
$this->cache->role($guildId, $roleId);
```

## What it holds, and what it does not

The cache fills from `GUILD_CREATE` and then keeps step with the events that follow:
channels, threads, roles, members and voice states. It only ever holds what your intents
already deliver — with no `GUILD_MEMBERS` intent there are no members in it.

Two limits are worth knowing before you lean on it:

- **Members are incomplete on a large guild.** Discord sends the full member list in
  `GUILD_CREATE` only for small guilds; beyond that it sends a slice. Read `members()` as
  what is known rather than as everyone.
- **A miss is a miss.** Reads never touch the network, so nothing silently turns into a
  rate-limited request inside a loop. A miss returns null; fetch it over REST yourself
  when you need certainty, and hand the result back so the next read is served from memory:

```php
$member = $this->cache->member($guildId, $userId)
    ?? await($this->discord->rest->guild->getMember($guildId, $userId));

$this->cache->putMember($guildId, $member);
```

Command, component and event handlers all run inside a fiber, so awaiting like that is
allowed anywhere your own code runs.

## Turning it off

The cache is on by default and can be switched off in `app/config/tempcord.config.php`:

```php
return new TempcordConfig(
    token: env('DISCORD_TOKEN') ?? '',
    intents: Bitwise::from(Intent::GUILDS),
    cache: false,
);
```
