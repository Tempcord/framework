<?php

use Monolog\Level;
use Monolog\LogRecord;
use Tempcord\Logging\Handlers\ConsoleLogHandler;
use function Tempest\get;

describe('ConsoleLogHandler', function (): void {
    it('skips messages matching except patterns', function (): void {
        $console = get(\Tempest\Console\Console::class);
        $handler = new ConsoleLogHandler($console, except: ['debug']);

        $method = new ReflectionMethod($handler, 'shouldSkipMessage');
        $method->setAccessible(true);

        expect($method->invoke($handler, 'debug info'))->toBeTrue()
            ->and($method->invoke($handler, 'other'))->toBeFalse();
    });

    it('formats and selects component based on level', function (): void {
        $console = get(\Tempest\Console\Console::class);
        $handler = new ConsoleLogHandler($console, includeTimestamp: false);

        $format = new ReflectionMethod($handler, 'formatMessage');
        $format->setAccessible(true);
        $component = new ReflectionMethod($handler, 'getComponent');
        $component->setAccessible(true);

        $infoRecord = new LogRecord(new DateTimeImmutable(), 'chan', Level::Info, 'hello');
        $warnRecord = new LogRecord(new DateTimeImmutable(), 'chan', Level::Warning, 'warn');
        $errorRecord = new LogRecord(new DateTimeImmutable(), 'chan', Level::Error, 'oops');

        expect($format->invoke($handler, $infoRecord))->toBe('Hello')
            ->and($format->invoke($handler, $warnRecord))->toBe('Warn')
            ->and($format->invoke($handler, $errorRecord))->toBe('Oops')
            ->and($component->invoke($handler, Level::Info))->toBe('info')
            ->and($component->invoke($handler, Level::Warning))->toBe('warning')
            ->and($component->invoke($handler, Level::Error))->toBe('error');
    });
});
