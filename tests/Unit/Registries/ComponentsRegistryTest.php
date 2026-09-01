<?php

namespace Tempcord\Tests\Unit\Registries;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Enums\ComponentKind;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Runtime\CustomId;
use Tempcord\Tests\Fixtures\ReportButton;
use Tempcord\Tests\Fixtures\TournamentButtons;
use Tempest\Reflection\ClassReflector;

#[CoversClass(ComponentsRegistry::class)]
final class ComponentsRegistryTest extends BaseTestCase
{
    /** @return list<ComponentDefinition> */
    private function compile(string $class): array
    {
        return new ComponentCompiler()->compile(new ClassReflector($class));
    }

    private function registry(string ...$classes): ComponentsRegistry
    {
        $registry = new ComponentsRegistry();

        foreach ($classes as $class) {
            foreach ($this->compile($class) as $definition) {
                $registry->add($definition);
            }
        }

        return $registry;
    }

    private function definition(ComponentKind $kind, string $pattern, string $handler): ComponentDefinition
    {
        return new ComponentDefinition(
            kind: $kind,
            customId: CustomId::compile($pattern),
            handler: $handler,
            method: new ClassReflector(ReportButton::class)->getMethod('__invoke'),
        );
    }

    public function test_an_empty_registry_matches_nothing(): void
    {
        $this->assertNull($this->registry()->match(ComponentKind::Button, 'report'));
        $this->assertSame(0, $this->registry()->count());
    }

    public function test_it_matches_a_literal_id(): void
    {
        $match = $this->registry(ReportButton::class)->match(ComponentKind::Button, 'report');

        $this->assertNotNull($match);
        $this->assertSame(ReportButton::class, $match->definition->handler);
        $this->assertSame([], $match->parameters);
    }

    public function test_it_matches_a_pattern_and_hands_back_its_values(): void
    {
        $match = $this->registry(TournamentButtons::class)
            ->match(ComponentKind::Button, 'tournament.accept.42');

        $this->assertNotNull($match);
        $this->assertSame('tournament.accept.{team}', $match->definition->customId->pattern);
        $this->assertSame(['team' => '42'], $match->parameters);
    }

    public function test_the_kind_is_part_of_the_match(): void
    {
        $registry = $this->registry(ReportButton::class);

        $this->assertNull($registry->match(ComponentKind::ModalSubmit, 'report'));
        $this->assertNull($registry->match(ComponentKind::SelectMenu, 'report'));
    }

    /**
     * A literal is an exception to a family of ids, so it must win regardless
     * of which was declared first.
     */
    public function test_a_literal_beats_a_pattern_that_would_also_match(): void
    {
        $registry = new ComponentsRegistry();
        $registry->add($this->definition(ComponentKind::Button, 'tournament.{rest}', 'Pattern'));
        $registry->add($this->definition(ComponentKind::Button, 'tournament.accept', 'Literal'));

        $this->assertSame(
            'Literal',
            $registry->match(ComponentKind::Button, 'tournament.accept')?->definition->handler,
        );
    }

    public function test_an_unmatched_id_resolves_to_nothing(): void
    {
        $this->assertNull(
            $this->registry(TournamentButtons::class)->match(ComponentKind::Button, 'petition.accept.1'),
        );
    }

    public function test_the_same_id_twice_is_refused(): void
    {
        $registry = new ComponentsRegistry();
        $registry->add($this->definition(ComponentKind::Button, 'report', 'First'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Two handlers answer the same button "report"');

        $registry->add($this->definition(ComponentKind::Button, 'report', 'Second'));
    }

    public function test_the_same_id_under_a_different_kind_is_allowed(): void
    {
        $registry = new ComponentsRegistry();
        $registry->add($this->definition(ComponentKind::Button, 'report', 'Button'));
        $registry->add($this->definition(ComponentKind::ModalSubmit, 'report', 'Modal'));

        $this->assertSame(2, $registry->count());
    }

    public function test_it_lists_literals_and_patterns_together(): void
    {
        $registry = $this->registry(ReportButton::class, TournamentButtons::class);

        $this->assertSame(4, $registry->count());
        $this->assertCount(4, $registry->all());
    }
}
