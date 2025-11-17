<?php

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\Intent;
use Tempcord\Registries\CommandsRegistry;
use Tests\Fixtures\Commands\SpyInvokableCommand;
use Tests\Fixtures\Commands\SpyGuildInvokableCommand;
use Ragnarok\Fenrir\Discord;
use function Tempest\get;

describe('Command registration', function (): void {
    beforeEach(function () {
        $this->registry = get(CommandsRegistry::class);
    });

    it('initializes filtered listener for a global command without errors', function (): void {
        $this->discover(SpyInvokableCommand::class);
        $discord = (new Discord('dummy-token'))->withGateway(Bitwise::from(Intent::GUILDS));
        $this->registry->initialize($discord);
        expect(true)->toBeTrue();
    });

    it('initializes filtered listener for a guild command without errors', function (): void {
        $this->discover(SpyGuildInvokableCommand::class);
        $discord = (new Discord('dummy-token'))->withGateway(Bitwise::from(Intent::GUILDS));
        $this->registry->initialize($discord);
        expect(true)->toBeTrue();
    });

    it('initializes filtered listener for a command group without errors', function (): void {
        \Tests\Fixtures\Commands\ParentWithSubcommandsCommand::$invoked = false;
        $this->discover(\Tests\Fixtures\Commands\ParentWithSubcommandsCommand::class);
        $discord = (new Discord('dummy-token'))->withGateway(Bitwise::from(Intent::GUILDS));
        $this->registry->initialize($discord);
        expect(true)->toBeTrue();
    });
});
