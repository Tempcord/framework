<?php

use Tempcord\Support\FenrirEventBus;
use Tests\Support\TestEmitter;

describe('FenrirEventBus', function (): void {
    it('forwards on and emit', function (): void {
        $bus = new FenrirEventBus(new TestEmitter());

        $called = false;
        $bus->on('evt', function ($arg) use (&$called) { $called = $arg === 42; });

        $bus->emit('evt', [42]);

        expect($called)->toBeTrue();
    });

    it('forwards once and emit for single-call', function (): void {
        $bus = new FenrirEventBus(new TestEmitter());

        $counter = 0;
        $bus->once('evt', function () use (&$counter) { $counter++; });

        $bus->emit('evt');
        $bus->emit('evt');

        expect($counter)->toBe(1);
    });
});

