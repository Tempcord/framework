<?php

use Tempcord\Attributes\Commands\Command;
use Tempcord\Registries\CommandsRegistry;
use Tests\Fixtures\Commands\CommandChatInputWithoutDescription;
use Tests\Fixtures\Commands\CommandWithDefinedName;
use Tests\Fixtures\Commands\CommandWithoutName;
use Tests\Fixtures\Commands\CommandWithSubcommand;
use Tests\Fixtures\Commands\CommandWithTwoSubcommands;
use Tests\Fixtures\Commands\HandledByCommand;
use Tests\Fixtures\Commands\InvokableCommand;
use function Tempest\get;


describe('Basic Commands', function (): void {
    beforeEach(function () {
        $this->registry = get(CommandsRegistry::class);
    });

    it('uses class name as command name when name is not provided', function (): void {
        $this->discover(CommandWithoutName::class);

        expect(array_keys($this->registry->bucket->items->toArray()))
            ->toContain('without_name')
            ->and($this->registry->bucket->items->toArray()['without_name']->name)
            ->toBe('without_name');
    });

    it('uses provided command name', function (): void {
        $this->discover(CommandWithDefinedName::class);

        expect(array_keys($this->registry->bucket->items->toArray()))
            ->toContain('with_custom_name')
            ->and($this->registry->bucket->items->toArray()['with_custom_name']->name)
            ->toBe('with_custom_name');
    });

    it('should have __invoke() method as handler by default', function (): void {
        $this->discover(InvokableCommand::class);

        /** @var Command $command */
        $command = $this->registry->bucket->items->get('invokable');
        $expectedMethod = new ReflectionMethod(new InvokableCommand(), '__invoke');

        expect($command->handlers->getReflection()->getName())
            ->toBe($expectedMethod->getName())
            ->and($command->handlers->getReflection()->isPublic())
            ->toBeTrue();
    });

    it('should use HandledBy method provided', function (): void {
        $this->discover(HandledByCommand::class);

        /** @var Command $command */
        $command = $this->registry->bucket->items->get('handled_by');
        $expectedMethod = new ReflectionMethod(new HandledByCommand(), 'handler_method');

        expect($command->handlers->getReflection()->getName())
            ->toBe($expectedMethod->getName())
            ->and($command->handlers->getReflection()->isPublic())              
            ->toBeTrue();
    });

    it('tells that description is required when command type is CHAT_INPUT', function (): void {
        $this->discover(CommandChatInputWithoutDescription::class);
    })
        ->throws(InvalidArgumentException::class, 'Description for command is required when type=CHAT_INPUT');
});

describe('Commands With Subcommands', function (): void {
    beforeEach(function () {
        $this->registry = get(CommandsRegistry::class);
    });
    it('discovers subcommands of the command', function (): void {
        $this->discover(CommandWithSubcommand::class);

        /** @var Command $command */
        $command = $this->registry->bucket->items->get('with_subcommand');
        $subcommands = $command->subCommands;

        expect($command->hasSubcommands)->toBeTrue()
            ->and($command->subCommands)->toHaveCount(1)
            ->and(array_shift($subcommands)->name)->toBe('with_subcommand_subcommand');
    });

    it('discovers all subcommands of the command', function (): void {
        $this->discover(CommandWithTwoSubcommands::class);

        $command = $this->registry->bucket->items->get('with_two_subcommands');
        $subcommands = $command->subCommands;

        expect($command->hasSubcommands)->toBeTrue()
            ->and($command->subCommands)->toHaveCount(2)
            ->and(array_shift($subcommands)->name)->toBe('with_two_subcommands_subcommand')
            ->and(array_shift($subcommands)->name)->toBe('with_two_subcommands_subcommand_two');

    });
});