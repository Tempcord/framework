<?php

namespace Tempcord\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Enums\ComponentKind;
use Tempcord\Tests\Fixtures\BanModal;
use Tempcord\Tests\Fixtures\HandlerlessButton;
use Tempcord\Tests\Fixtures\PunishmentSelectMenu;
use Tempcord\Tests\Fixtures\ReportButton;
use Tempcord\Tests\Fixtures\TournamentButtons;
use Tempest\Reflection\ClassReflector;

#[CoversClass(ComponentCompiler::class)]
final class ComponentCompilerTest extends BaseTestCase
{
    /** @return list<ComponentDefinition> */
    private function compile(string $class): array
    {
        return new ComponentCompiler()->compile(new ClassReflector($class));
    }

    /** @return list<string> */
    private function patterns(array $definitions): array
    {
        return array_map(
            static fn(ComponentDefinition $definition) => $definition->customId->pattern,
            $definitions,
        );
    }

    public function test_a_class_without_component_attributes_compiles_to_nothing(): void
    {
        $this->assertSame([], $this->compile(\Tempcord\Tests\Fixtures\PingCommand::class));
    }

    public function test_a_button_defaults_to_its_class_name_without_the_affix(): void
    {
        $definitions = $this->compile(ReportButton::class);

        $this->assertCount(1, $definitions);
        $this->assertSame('report', $definitions[0]->customId->pattern);
        $this->assertSame(ComponentKind::Button, $definitions[0]->kind);
        $this->assertSame(ReportButton::class, $definitions[0]->handler);
        $this->assertSame('__invoke', $definitions[0]->method->getName());
    }

    public function test_a_select_menu_drops_its_own_affix(): void
    {
        $definitions = $this->compile(PunishmentSelectMenu::class);

        $this->assertSame(['punishment'], $this->patterns($definitions));
        $this->assertSame(ComponentKind::SelectMenu, $definitions[0]->kind);
    }

    public function test_an_explicit_id_wins_over_the_class_name(): void
    {
        $definitions = $this->compile(BanModal::class);

        $this->assertSame(['ban.{member}'], $this->patterns($definitions));
        $this->assertSame(ComponentKind::ModalSubmit, $definitions[0]->kind);
    }

    public function test_attributes_on_methods_each_become_a_handler(): void
    {
        $definitions = $this->compile(TournamentButtons::class);

        $this->assertSame(
            ['tournament.accept.{team}', 'tournament.reject.{team}', 'tournament.drop.{team}'],
            $this->patterns($definitions),
        );
    }

    public function test_a_method_handler_points_at_that_method(): void
    {
        $definitions = $this->compile(TournamentButtons::class);

        $this->assertSame('accept', $definitions[0]->method->getName());
        $this->assertSame('reject', $definitions[1]->method->getName());
        $this->assertSame('reject', $definitions[2]->method->getName());
    }

    public function test_a_class_attribute_without_an_invoke_method_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('should declare an __invoke method');

        $this->compile(HandlerlessButton::class);
    }
}
