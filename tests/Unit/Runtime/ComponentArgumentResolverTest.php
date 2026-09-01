<?php

namespace Tempcord\Tests\Unit\Runtime;

use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use CyberWolf\Discord\Interaction\ButtonInteraction;
use CyberWolf\Discord\Interaction\ComponentInteraction;
use CyberWolf\Discord\Interaction\ModalSubmitInteraction;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Runtime\ComponentArgumentResolver;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\Interactions;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\Severity;
use Tempcord\Tests\Fixtures\WrapperButtons;
use Tempest\Reflection\ClassReflector;

#[CoversClass(ComponentArgumentResolver::class)]
final class ComponentArgumentResolverTest extends BaseTestCase
{
    private function definition(string $pattern): ComponentDefinition
    {
        foreach (new ComponentCompiler()->compile(new ClassReflector(WrapperButtons::class)) as $definition) {
            if ($definition->customId->pattern === $pattern) {
                return $definition;
            }
        }

        $this->fail('No fixture handler for ' . $pattern);
    }

    /**
     * @param array<string, string> $parameters
     * @return list<mixed>
     */
    private function resolve(string $pattern, InteractionCreate $interaction, array $parameters = []): array
    {
        return new ComponentArgumentResolver(new FakeDiscord(new RecordingHttp()))
            ->resolve($this->definition($pattern), $interaction, $parameters);
    }

    public function test_it_passes_the_raw_gateway_payload_when_that_is_the_type(): void
    {
        $interaction = Interactions::button('raw');

        $this->assertSame([$interaction], $this->resolve('raw', $interaction));
    }

    public function test_it_wraps_the_payload_in_the_type_the_handler_declared(): void
    {
        $this->assertInstanceOf(
            ButtonInteraction::class,
            $this->resolve('wrapped', Interactions::button('wrapped'))[0],
        );

        $this->assertInstanceOf(
            ComponentInteraction::class,
            $this->resolve('component', Interactions::button('component'))[0],
        );

        $this->assertInstanceOf(
            ModalSubmitInteraction::class,
            $this->resolve('submitted', Interactions::modal('submitted'))[0],
        );
    }

    public function test_placeholders_are_cast_to_the_parameter_types(): void
    {
        $this->assertSame(
            [3, 0.5, true],
            $this->resolve(
                'typed.{count}.{ratio}.{flag}',
                Interactions::button('typed.3.0.5.true'),
                ['count' => '3', 'ratio' => '0.5', 'flag' => 'true'],
            ),
        );
    }

    public function test_a_placeholder_becomes_the_backed_enum_it_is_typed_as(): void
    {
        $this->assertSame(
            [Severity::High],
            $this->resolve('severity.{level}', Interactions::button('severity.high'), ['level' => 'high']),
        );
    }

    public function test_a_parameter_nothing_supplies_is_reported_against_its_handler(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: missing for button "required.{given}"');

        $this->resolve('required.{given}', Interactions::button('required.x'), ['given' => 'x']);
    }
}
