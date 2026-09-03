<?php

namespace Tempcord\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Enums\Permission;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\GuildMember;
use Tempcord\Discord\Parts\InteractionData;
use Tempcord\Middleware\RequiresPermissions;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(RequiresPermissions::class)]
final class RequiresPermissionsTest extends TestCase
{
    private RecordingHttp $http;

    protected function setUp(): void
    {
        $this->http = new RecordingHttp();
    }

    /**
     * Discord sends the caller's permissions as a decimal string, because the
     * bitfield outgrew what JSON can carry as a number.
     */
    private function interaction(?string $permissions): CommandInteraction
    {
        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = new InteractionData();

        if ($permissions !== null) {
            $member = new GuildMember();
            $member->roles = [];
            $member->permissions = $permissions;

            $interaction->member = $member;
        }

        return new CommandInteraction($interaction, new FakeDiscord($this->http));
    }

    private function reaches(RequiresPermissions $middleware, ?string $permissions): bool
    {
        $reached = false;

        $middleware($this->interaction($permissions), function () use (&$reached): void {
            $reached = true;
        });

        return $reached;
    }

    public function test_a_member_holding_the_permission_gets_through(): void
    {
        $this->assertTrue($this->reaches(
            new RequiresPermissions([Permission::MANAGE_GUILD]),
            (string) Permission::MANAGE_GUILD->value,
        ));
    }

    public function test_a_member_without_it_is_refused(): void
    {
        $this->assertFalse($this->reaches(
            new RequiresPermissions([Permission::MANAGE_GUILD]),
            (string) Permission::SEND_MESSAGES->value,
        ));
    }

    public function test_every_permission_named_has_to_be_held(): void
    {
        $this->assertFalse($this->reaches(
            new RequiresPermissions([Permission::MANAGE_GUILD, Permission::BAN_MEMBERS]),
            (string) Permission::MANAGE_GUILD->value,
        ));
    }

    public function test_an_administrator_holds_everything(): void
    {
        $this->assertTrue($this->reaches(
            new RequiresPermissions([Permission::MANAGE_GUILD, Permission::BAN_MEMBERS]),
            (string) Permission::ADMINISTRATOR->value,
        ));
    }

    /**
     * A permission above the 31st bit is the reason the field arrives as a
     * string at all, so one of those is worth checking on its own.
     */
    public function test_a_permission_beyond_a_32_bit_field_is_read_correctly(): void
    {
        $this->assertTrue($this->reaches(
            new RequiresPermissions([Permission::MODERATE_MEMBERS]),
            (string) Permission::MODERATE_MEMBERS->value,
        ));
    }

    /**
     * Nobody holds a guild permission in a direct message, so nobody clears
     * a check for one.
     */
    public function test_an_interaction_with_no_member_is_refused(): void
    {
        $this->assertFalse($this->reaches(new RequiresPermissions([Permission::MANAGE_GUILD]), null));
    }

    public function test_a_refusal_is_told_to_the_caller_and_nobody_else(): void
    {
        $this->reaches(
            new RequiresPermissions([Permission::MANAGE_GUILD], 'Тільки для модерації.'),
            (string) Permission::SEND_MESSAGES->value,
        );

        $data = $this->http->posts[0]['content']['data'] ?? [];

        $this->assertSame('Тільки для модерації.', $data['content'] ?? null);
        $this->assertSame(64, $data['flags'] ?? null, 'an ephemeral reply carries the EPHEMERAL flag');
    }
}
