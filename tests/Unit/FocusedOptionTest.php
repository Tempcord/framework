<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use ReflectionMethod;
use Tempcord\AllCommandExtension;
use Tempcord\Attributes\Command;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Tests\Fixtures\PingCommand;

#[CoversClass(CommandsRegistry::class)]
final class FocusedOptionTest extends TestCase
{
    private function option(string $name, bool $focused = false): ApplicationCommandInteractionDataOptionStructure
    {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = $name;
        $option->type = ApplicationCommandOptionType::STRING;
        $option->value = 'whatever';
        $option->options = [];
        $option->focused = $focused;

        return $option;
    }

    private function resolve(array $interactionOptions, Command $definition): ?array
    {
        return new ReflectionMethod(CommandsRegistry::class, 'resolveFocusedAndParam')
            ->invoke(new CommandsRegistry(new AllCommandExtension()), $interactionOptions, $definition);
    }

    public function test_it_resolves_the_focused_option_against_the_definition(): void
    {
        $command = $this->command(PingCommand::class);
        $focused = $this->option('name', focused: true);

        $resolved = $this->resolve([$this->option('times'), $focused], $command);

        $this->assertNotNull($resolved);
        $this->assertSame($command->options['name'], $resolved[0]);
        $this->assertSame($focused, $resolved[1]);
    }

    public function test_it_returns_null_when_nothing_is_focused(): void
    {
        $this->assertNull(
            $this->resolve([$this->option('name'), $this->option('times')], $this->command(PingCommand::class)),
        );
    }

    /**
     * A focused option the command does not declare must not raise an
     * "Undefined array key" warning — it simply does not resolve.
     */
    public function test_a_focused_option_unknown_to_the_definition_resolves_to_null(): void
    {
        $this->assertNull(
            $this->resolve([$this->option('not_declared', focused: true)], $this->command(PingCommand::class)),
        );
    }

    public function test_it_returns_null_for_no_options_at_all(): void
    {
        $this->assertNull($this->resolve([], $this->command(PingCommand::class)));
    }
}
