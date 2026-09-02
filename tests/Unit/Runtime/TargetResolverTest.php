<?php

namespace Tempcord\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\GuildMember;
use Tempcord\Discord\Parts\InteractionData;
use Tempcord\Discord\Parts\InteractionDataResolved;
use Tempcord\Discord\Parts\Message;
use Tempcord\Discord\Parts\User;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Runtime\TargetResolver;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use LogicException;
use Tempcord\Tests\Fixtures\BareCommand;
use Tempcord\Tests\Fixtures\DescribedContextMenu;
use Tempcord\Tests\Fixtures\MemberContextMenu;
use Tempcord\Tests\Fixtures\ProfileContextMenu;
use Tempcord\Tests\Fixtures\ReportContextMenu;
use Tempcord\Tests\Unit\TestCase;

/**
 * What a context menu was used on.
 *
 * A context menu carries no options, so there is nothing for the option
 * resolver to find — without this a handler could only take the raw
 * interaction and dig the target out of the payload by hand.
 */
#[CoversClass(TargetResolver::class)]
#[CoversClass(ArgumentResolver::class)]
final class TargetResolverTest extends TestCase
{
    private function user(string $id): User
    {
        $user = new User();
        $user->id = $id;
        $user->username = 'user-' . $id;

        return $user;
    }

    /**
     * A context menu interaction as Discord sends one: the target named by id,
     * and the object itself alongside.
     */
    private function interaction(string $name, string $targetId, ?callable $fill = null): CommandInteraction
    {
        $resolved = new InteractionDataResolved();

        if ($fill !== null) {
            $fill($resolved);
        }

        $data = new InteractionData();
        $data->name = $name;
        $data->target_id = $targetId;
        $data->resolved = $resolved;
        $data->options = [];

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = $data;

        return new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp()));
    }

    /**
     * @return list<mixed>
     */
    private function arguments(string $class, string $path, CommandInteraction $interaction): array
    {
        $handler = $this->definition($class)->handlers[$path];

        return new ArgumentResolver(
            new OptionValueResolver(new FakeDiscord(new RecordingHttp())),
        )->resolve($handler, $interaction);
    }

    public function test_a_user_menu_hands_over_the_user_it_was_used_on(): void
    {
        $interaction = $this->interaction('Профіль', '99', static function (InteractionDataResolved $r): void {
            $r->users = ['99' => new User()];
            $r->users['99']->id = '99';
        });

        $arguments = $this->arguments(ProfileContextMenu::class, 'Профіль', $interaction);

        $this->assertSame($interaction, $arguments[0]);
        $this->assertInstanceOf(User::class, $arguments[1]);
        $this->assertSame('99', $arguments[1]->id);
    }

    public function test_a_message_menu_hands_over_the_message(): void
    {
        $message = new Message();
        $message->id = 'm1';

        $interaction = $this->interaction('Поскаржитись', 'm1', static function (InteractionDataResolved $r) use ($message): void {
            $r->messages = ['m1' => $message];
        });

        $arguments = $this->arguments(ReportContextMenu::class, 'Поскаржитись', $interaction);

        $this->assertSame($message, $arguments[1]);
    }

    /**
     * Discord sends the member and the user separately, leaving the member's
     * own user empty because it would only repeat what is already there. A
     * handler asking for a member still expects to reach their id.
     */
    public function test_a_member_target_is_given_back_its_user(): void
    {
        $member = new GuildMember();
        $member->user = null;
        $member->nick = 'Vlad';

        $interaction = $this->interaction('Ролі', '99', function (InteractionDataResolved $r) use ($member): void {
            $r->members = ['99' => $member];
            $r->users = ['99' => $this->user('99')];
        });

        $arguments = $this->arguments(MemberContextMenu::class, 'Ролі', $interaction);

        $this->assertInstanceOf(GuildMember::class, $arguments[1]);
        $this->assertSame('Vlad', $arguments[1]->nick);
        $this->assertSame('99', $arguments[1]->user?->id);
    }

    /**
     * A member Discord did send a user for keeps it rather than being
     * overwritten.
     */
    public function test_a_member_that_already_carries_a_user_is_left_alone(): void
    {
        $member = new GuildMember();
        $member->user = $this->user('original');

        $interaction = $this->interaction('Ролі', '99', function (InteractionDataResolved $r) use ($member): void {
            $r->members = ['99' => $member];
            $r->users = ['99' => $this->user('99')];
        });

        $arguments = $this->arguments(MemberContextMenu::class, 'Ролі', $interaction);

        $this->assertSame('original', $arguments[1]->user?->id);
    }

    /**
     * A target Discord named but did not resolve leaves the parameter unfilled,
     * which is a missing required argument rather than a silent null.
     */
    public function test_an_unresolved_target_is_reported(): void
    {
        $this->expectExceptionMessage('Missing required parameter: target');

        $this->arguments(
            ProfileContextMenu::class,
            'Профіль',
            $this->interaction('Профіль', '99'),
        );
    }

    /**
     * An ordinary slash command has no target, and nothing about how its
     * arguments are filled changes.
     */
    public function test_a_slash_command_is_untouched(): void
    {
        $data = new InteractionData();
        $data->name = 'bare';
        $data->options = [];

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = $data;

        $arguments = $this->arguments(
            BareCommand::class,
            'bare',
            new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp())),
        );

        $this->assertSame([], $arguments);
    }

    public function test_a_context_menu_carries_no_description_and_keeps_its_type(): void
    {
        $definition = $this->definition(ProfileContextMenu::class);

        $this->assertSame(ApplicationCommandTypes::USER, $definition->type);
        $this->assertNull($definition->description);
        $this->assertSame([], $definition->options);
    }

    /**
     * Discord shows a context menu without a description, so one written here
     * would be dropped on the way out — which is worth saying rather than
     * leaving someone to wonder why it never appears.
     */
    public function test_a_context_menu_with_a_description_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('shows without a description');

        $this->definition(DescribedContextMenu::class);
    }
}
