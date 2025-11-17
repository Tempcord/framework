<?php

namespace Tests\Support;

use Evenement\EventEmitterInterface;

class TestEmitter implements EventEmitterInterface
{
    private array $once = [];
    private array $on = [];

    public function on($event, callable $listener): void { $this->on[$event][] = $listener; }
    public function once($event, callable $listener): void { $this->once[$event][] = $listener; }
    public function removeListener($event, callable $listener): void {}
    public function removeAllListeners($event = null): void {}
    public function listeners($event = null): array { return []; }
    public function emit($event, array $arguments = []): void {
        foreach (($this->on[$event] ?? []) as $listener) { $listener(...$arguments); }
        foreach (($this->once[$event] ?? []) as $listener) { $listener(...$arguments); }
        $this->once[$event] = [];
    }
}

