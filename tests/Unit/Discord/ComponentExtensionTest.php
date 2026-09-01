<?php

namespace Tempcord\Tests\Unit\Discord;

use CyberWolf\Discord\Constants\Events;
use CyberWolf\Discord\Enums\InteractionType;
use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Discord\ComponentExtension;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\Interactions;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\BanModal;
use Tempcord\Tests\Fixtures\PunishmentSelectMenu;
use Tempcord\Tests\Fixtures\ReportButton;
use Tempcord\Tests\Fixtures\TournamentButtons;
use Tempest\Reflection\ClassReflector;

#[CoversClass(ComponentExtension::class)]
final class ComponentExtensionTest extends BaseTestCase
{
    private ComponentsRegistry $registry;
    private ComponentExtension $extension;
    private FakeDiscord $discord;

    /** @var list<array{InteractionCreate, array<string, string>}> */
    private array $received = [];

    protected function setUp(): void
    {
        $this->registry = new ComponentsRegistry();
        $this->extension = new ComponentExtension($this->registry);
        $this->discord = new FakeDiscord(new RecordingHttp());
        $this->extension->initialize($this->discord);
    }

    private function register(string ...$classes): void
    {
        foreach ($classes as $class) {
            foreach (new ComponentCompiler()->compile(new ClassReflector($class)) as $definition) {
                $this->registry->add($definition);
                $this->extension->bind($definition, function (InteractionCreate $interaction, array $parameters): void {
                    $this->received[] = [$interaction, $parameters];
                });
            }
        }
    }

    private function arrive(InteractionCreate $interaction): void
    {
        $this->discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interaction]);
    }

    public function test_a_button_press_reaches_the_handler_bound_to_its_id(): void
    {
        $this->register(ReportButton::class);

        $this->arrive(Interactions::button('report'));

        $this->assertCount(1, $this->received);
        $this->assertSame([], $this->received[0][1]);
    }

    public function test_it_hands_the_matched_placeholders_to_the_listener(): void
    {
        $this->register(TournamentButtons::class);

        $this->arrive(Interactions::button('tournament.accept.42'));

        $this->assertSame(['team' => '42'], $this->received[0][1]);
    }

    public function test_a_select_menu_and_a_modal_reach_their_own_handlers(): void
    {
        $this->register(PunishmentSelectMenu::class, BanModal::class);

        $this->arrive(Interactions::selectMenu('punishment', ['kick']));
        $this->arrive(Interactions::modal('ban.7', ['reason' => 'spam']));

        $this->assertCount(2, $this->received);
        $this->assertSame(['member' => '7'], $this->received[1][1]);
    }

    public function test_an_id_nothing_answers_is_ignored(): void
    {
        $this->register(ReportButton::class);

        $this->arrive(Interactions::button('some.other.bot'));

        $this->assertSame([], $this->received);
    }

    /**
     * A slash command arrives on the same gateway event and must not be read as
     * a component; the command extension owns it.
     */
    public function test_a_command_interaction_is_left_alone(): void
    {
        $this->register(ReportButton::class);

        $interaction = Interactions::button('report');
        $interaction->type = InteractionType::APPLICATION_COMMAND;

        $this->arrive($interaction);

        $this->assertSame([], $this->received);
    }

    public function test_a_button_and_a_modal_may_share_a_custom_id(): void
    {
        $this->register(ReportButton::class);

        $this->arrive(Interactions::modal('report'));

        $this->assertSame([], $this->received);
    }
}
