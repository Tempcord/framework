<?php

use Tempcord\Registries\CommandsRegistry;
use Tests\Fixtures\Commands\CommandWithSubcommand;
use Tests\Fixtures\Commands\CommandWithTwoSubcommands;
use function Tempest\get;

describe('Command Options', function (): void {
    beforeEach(function () {
        $this->registry = get(CommandsRegistry::class);
    });

    it('returns subcommands as options for single subcommand', function (): void {
        $this->discover(CommandWithSubcommand::class);

        $command = $this->registry->bucket->items->get('with_subcommand');
        $options = $command->options;

        expect(count($options))->toBe(1);
        expect(array_keys($command->subCommands))->toContain('subcommand');
    });

    it('returns all subcommands as options for multiple subcommands', function (): void {
        $this->discover(CommandWithTwoSubcommands::class);

        $command = $this->registry->bucket->items->get('with_two_subcommands');
        $options = $command->options;

        expect(count($options))->toBe(2);
        expect(array_keys($command->subCommands))->toContain('subcommand');
        expect(array_keys($command->subCommands))->toContain('subcommand_two');
    });
});
