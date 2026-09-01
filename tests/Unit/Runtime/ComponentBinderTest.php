<?php

namespace Tempcord\Tests\Unit\Runtime;

use Tempcord\Discord\Constants\Events;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Discord\ComponentExtension;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Runtime\ComponentArgumentResolver;
use Tempcord\Runtime\ComponentBinder;
use Tempcord\Runtime\ComponentDispatcher;
use Tempcord\Runtime\Outcome;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\Interactions;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\BanModal;
use Tempcord\Tests\Fixtures\PunishmentSelectMenu;
use Tempcord\Tests\Fixtures\ReportButton;
use Tempcord\Tests\Fixtures\ThrowingButton;
use Tempcord\Tests\Fixtures\TournamentButtons;
use Tempest\Container\GenericContainer;
use Tempest\Reflection\ClassReflector;

/**
 * The whole component path, end to end: attributes are compiled, bound, and an
 * interaction pushed onto the gateway runs the method with its arguments filled.
 */
#[CoversClass(ComponentBinder::class)]
#[CoversClass(ComponentDispatcher::class)]
#[CoversClass(ComponentArgumentResolver::class)]
final class ComponentBinderTest extends BaseTestCase
{
    private FakeDiscord $discord;
    private ComponentsRegistry $registry;
    private ComponentBinder $binder;
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        ReportButton::$presses = [];
        TournamentButtons::$calls = [];
        PunishmentSelectMenu::$calls = [];
        BanModal::$calls = [];

        $this->discord = new FakeDiscord(new RecordingHttp());
        $this->registry = new ComponentsRegistry();
        $this->logger = new RecordingLogger();

        $extension = new ComponentExtension($this->registry);
        $extension->initialize($this->discord);

        $this->binder = new ComponentBinder(
            $extension,
            new ComponentDispatcher(
                new ComponentArgumentResolver($this->discord),
                new GenericContainer(),
                $this->logger,
            ),
        );
    }

    /** @return list<string> */
    private function bind(string ...$classes): array
    {
        foreach ($classes as $class) {
            foreach (new ComponentCompiler()->compile(new ClassReflector($class)) as $definition) {
                $this->registry->add($definition);
            }
        }

        return array_map(
            static fn(Outcome $outcome) => $outcome->message,
            $this->binder->bindAll($this->registry->all()),
        );
    }

    private function arrive(InteractionCreate $interaction): void
    {
        $this->discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interaction]);
    }

    public function test_it_reports_what_it_bound(): void
    {
        $this->assertSame(
            ['Listening button "report".'],
            $this->bind(ReportButton::class),
        );
    }

    public function test_binding_nothing_reports_nothing(): void
    {
        $this->assertSame([], $this->binder->bindAll([]));
    }

    public function test_a_button_handler_receives_the_interaction_it_asked_for(): void
    {
        $this->bind(ReportButton::class);

        $this->arrive(Interactions::button('report'));

        $this->assertCount(1, ReportButton::$presses);
    }

    public function test_a_placeholder_reaches_the_parameter_of_the_same_name(): void
    {
        $this->bind(TournamentButtons::class);

        $this->arrive(Interactions::button('tournament.accept.alpha'));

        $this->assertSame([['accept', 'alpha']], TournamentButtons::$calls);
    }

    public function test_a_placeholder_is_cast_to_the_parameter_type(): void
    {
        $this->bind(TournamentButtons::class);

        $this->arrive(Interactions::button('tournament.reject.42'));

        $this->assertSame([['reject', 42]], TournamentButtons::$calls);
    }

    public function test_one_method_answers_every_id_it_declares(): void
    {
        $this->bind(TournamentButtons::class);

        $this->arrive(Interactions::button('tournament.drop.7'));

        $this->assertSame([['reject', 7]], TournamentButtons::$calls);
    }

    public function test_a_select_menu_handler_receives_what_was_picked(): void
    {
        $this->bind(PunishmentSelectMenu::class);

        $this->arrive(Interactions::selectMenu('punishment', ['kick', 'ban']));

        $this->assertSame([['kick', ['kick', 'ban']]], PunishmentSelectMenu::$calls);
    }

    public function test_an_empty_select_menu_leaves_the_value_null(): void
    {
        $this->bind(PunishmentSelectMenu::class);

        $this->arrive(Interactions::selectMenu('punishment'));

        $this->assertSame([[null, []]], PunishmentSelectMenu::$calls);
    }

    public function test_a_modal_field_reaches_the_parameter_of_the_same_name(): void
    {
        $this->bind(BanModal::class);

        $this->arrive(Interactions::modal('ban.99', ['reason' => 'spam', 'duration' => '7d']));

        $this->assertSame([['99', 'spam', '7d']], BanModal::$calls);
    }

    /**
     * A field the user left out is not supplied at all, so the parameter's own
     * default applies rather than a null being forced onto it.
     */
    public function test_a_missing_modal_field_falls_back_to_the_default(): void
    {
        $this->bind(BanModal::class);

        $this->arrive(Interactions::modal('ban.99', ['reason' => 'spam']));

        $this->assertSame([['99', 'spam', 'forever']], BanModal::$calls);
    }

    public function test_a_handler_that_throws_is_logged_rather_than_escaping(): void
    {
        $this->bind(ThrowingButton::class);

        $this->arrive(Interactions::button('throwing'));

        $this->assertSame(
            ['Handler for button "throwing" failed: nope'],
            $this->logger->messages,
        );
    }
}
