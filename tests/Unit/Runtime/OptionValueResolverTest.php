<?php

namespace Tempcord\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\InteractionData;
use RuntimeException;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(OptionValueResolver::class)]
final class OptionValueResolverTest extends TestCase
{
    private function option(ApplicationCommandOptionType $type, mixed $value): ApplicationCommandInteractionDataOptionStructure
    {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = 'subject';
        $option->type = $type;
        $option->value = $value;
        $option->options = [];

        return $option;
    }

    private function interaction(FakeDiscord $discord): CommandInteraction
    {
        $data = new InteractionData();
        $data->name = 'whatever';
        $data->options = [];

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->guild_id = '999';
        $interaction->data = $data;

        return new CommandInteraction($interaction, $discord);
    }

    public function test_a_missing_option_resolves_to_null(): void
    {
        $http = new RecordingHttp();
        $discord = new FakeDiscord($http);

        $this->assertNull(
            new OptionValueResolver($discord)->resolve(null, $this->interaction($discord)),
        );
        $this->assertSame([], $http->gets);
    }

    public function test_a_scalar_option_is_passed_through_without_touching_discord(): void
    {
        $http = new RecordingHttp();
        $discord = new FakeDiscord($http);

        $this->assertSame(
            'hello',
            new OptionValueResolver($discord)->resolve(
                $this->option(ApplicationCommandOptionType::STRING, 'hello'),
                $this->interaction($discord),
            ),
        );
        $this->assertSame([], $http->gets);
    }

    /**
     * A user option used to be fetched twice: once in a standalone if-block
     * whose result was discarded, then again in the match below it.
     */
    public function test_a_user_option_is_fetched_exactly_once(): void
    {
        $http = new RecordingHttp();
        $discord = new FakeDiscord($http);

        new OptionValueResolver($discord)->resolve(
            $this->option(ApplicationCommandOptionType::USER, '77'),
            $this->interaction($discord),
        );

        $this->assertSame(['users/77'], $http->gets);
    }

    public function test_a_channel_option_is_fetched_once(): void
    {
        $http = new RecordingHttp();
        $discord = new FakeDiscord($http);

        new OptionValueResolver($discord)->resolve(
            $this->option(ApplicationCommandOptionType::CHANNEL, '88'),
            $this->interaction($discord),
        );

        $this->assertSame(['channels/88'], $http->gets);
    }

    public function test_a_role_option_is_looked_up_against_the_interactions_guild(): void
    {
        $http = new RecordingHttp();
        $discord = new FakeDiscord($http);

        new OptionValueResolver($discord)->resolve(
            $this->option(ApplicationCommandOptionType::ROLE, '66'),
            $this->interaction($discord),
        );

        $this->assertSame(['guilds/999/roles/66'], $http->gets);
    }

    public function test_a_mentionable_option_is_reported_as_unsupported(): void
    {
        $discord = new FakeDiscord(new RecordingHttp());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mentionable options are not supported yet');

        new OptionValueResolver($discord)->resolve(
            $this->option(ApplicationCommandOptionType::MENTIONABLE, '1'),
            $this->interaction($discord),
        );
    }
}
