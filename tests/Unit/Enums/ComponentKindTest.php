<?php

namespace Tempcord\Tests\Unit\Enums;

use Tempcord\Discord\Enums\InteractionType;
use Tempcord\Discord\Enums\MessageComponentType;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Enums\ComponentKind;
use Tempcord\Tests\Doubles\Interactions;

#[CoversClass(ComponentKind::class)]
final class ComponentKindTest extends BaseTestCase
{
    public function test_a_button_press_is_a_button(): void
    {
        $this->assertSame(ComponentKind::Button, ComponentKind::of(Interactions::button('x')));
    }

    public function test_a_submitted_modal_is_a_modal_submit(): void
    {
        $this->assertSame(ComponentKind::ModalSubmit, ComponentKind::of(Interactions::modal('x')));
    }

    /**
     * Discord has five select types and reports them all the same way, so one
     * kind covers the lot.
     */
    #[DataProvider('selectTypes')]
    public function test_every_select_type_is_a_select_menu(MessageComponentType $type): void
    {
        $interaction = Interactions::selectMenu('x');
        $interaction->data->component_type = $type;

        $this->assertSame(ComponentKind::SelectMenu, ComponentKind::of($interaction));
    }

    public static function selectTypes(): iterable
    {
        yield 'string' => [MessageComponentType::STRING_SELECT];
        yield 'user' => [MessageComponentType::USER_SELECT];
        yield 'role' => [MessageComponentType::ROLE_SELECT];
        yield 'mentionable' => [MessageComponentType::MENTIONABLE_SELECT];
        yield 'channel' => [MessageComponentType::CHANNEL_SELECT];
    }

    public function test_a_command_interaction_is_not_a_component(): void
    {
        $interaction = Interactions::button('x');
        $interaction->type = InteractionType::APPLICATION_COMMAND;

        $this->assertNull(ComponentKind::of($interaction));
    }

    public function test_an_interaction_without_a_type_is_not_a_component(): void
    {
        $this->assertNull(ComponentKind::of(new InteractionCreate()));
    }

    /**
     * A text input never arrives on its own; it comes inside a modal submit.
     */
    public function test_a_component_type_that_never_fires_alone_is_not_a_component(): void
    {
        $interaction = Interactions::button('x');
        $interaction->data->component_type = MessageComponentType::TEXT_INPUT;

        $this->assertNull(ComponentKind::of($interaction));
    }
}
