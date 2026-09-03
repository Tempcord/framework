<?php

namespace Tempcord\Tests\Unit\Compiler;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Attributes\Command;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Enums\EntryPointCommandHandlerType;
use Tempcord\Tests\Fixtures\LauncherCommand;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Reflection\ClassReflector;

/**
 * The one command an app with Activities has in the launcher.
 *
 * Discord creates it itself when Activities are turned on, and deletes it
 * again the moment an app overwrites its command set without it — so an app
 * has to be able to describe one even though it never handles it.
 */
#[CoversClass(CommandCompiler::class)]
#[CoversClass(CommandBuilderFactory::class)]
final class EntryPointCommandTest extends TestCase
{
    public function test_it_compiles_without_a_method_to_call(): void
    {
        $definition = $this->definition(LauncherCommand::class);

        $this->assertSame(ApplicationCommandTypes::PRIMARY_ENTRY_POINT, $definition->type);
        $this->assertSame(EntryPointCommandHandlerType::DISCORD_LAUNCH_ACTIVITY, $definition->handler);
        $this->assertSame([], $definition->handlers);
    }

    public function test_the_payload_carries_the_type_and_the_handler(): void
    {
        $payload = new CommandBuilderFactory()->payloadFor($this->definition(LauncherCommand::class));

        $this->assertSame(4, $payload['type']);
        $this->assertSame(2, $payload['handler']);
        $this->assertSame('Open the app', $payload['description']);
    }

    /**
     * Discord shows it in the launcher, so unlike a context menu it needs one.
     */
    public function test_an_entry_point_without_a_description_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PRIMARY_ENTRY_POINT');

        new CommandCompiler()->compile(
            new ClassReflector(LauncherCommand::class),
            new Command(
                name: 'launch',
                type: ApplicationCommandTypes::PRIMARY_ENTRY_POINT,
                handler: EntryPointCommandHandlerType::DISCORD_LAUNCH_ACTIVITY,
            ),
        );
    }

    public function test_an_entry_point_must_say_who_answers_it(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must say which handler answers it');

        new CommandCompiler()->compile(
            new ClassReflector(LauncherCommand::class),
            new Command(
                name: 'launch',
                description: 'Open the app',
                type: ApplicationCommandTypes::PRIMARY_ENTRY_POINT,
            ),
        );
    }

    public function test_only_an_entry_point_may_declare_a_handler(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('only an entry point command has');

        new CommandCompiler()->compile(
            new ClassReflector(LauncherCommand::class),
            new Command(
                name: 'launch',
                description: 'Open the app',
                handler: EntryPointCommandHandlerType::DISCORD_LAUNCH_ACTIVITY,
            ),
        );
    }
}
