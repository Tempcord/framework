<?php

use Tempcord\Registries\CommandsRegistry;
use Tests\Fixtures\Commands\SpyInvokableCommand;
use Tests\Fixtures\Commands\CommandWithTwoSubcommands;
use function Tempest\get;

describe('Command Builder', function (): void {
    beforeEach(function () {
        $this->registry = get(CommandsRegistry::class);
    });

    it('builds name and description for invokable command', function (): void {
        $this->discover(SpyInvokableCommand::class);

        /** @var \Tempcord\Attributes\Commands\Command $command */
        $command = $this->registry->bucket->items->get('spy_invokable');
        $builder = $command->builder;

        $data = (new ReflectionClass($builder))->getProperty('data');
        $data->setAccessible(true);
        $payload = $data->getValue($builder);

        expect($payload['name'])->toBe('spy_invokable')
            ->and($payload['description'])->toBe('spy invokable')
            ->and($payload['type'])->toBe(1);
    });

    it('exposes subcommands via options without building', function (): void {
        $this->discover(CommandWithTwoSubcommands::class);

        $command = $this->registry->bucket->items->get('with_two_subcommands');

        expect($command->hasSubcommands)->toBeTrue()
            ->and(count($command->options))->toBeGreaterThan(0);
    });
});
