<?php

namespace Tempcord\Tests\Unit\Discord;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Enums\ChannelType;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Tests\Fixtures\ConstrainedCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(CommandBuilderFactory::class)]
final class OptionConstraintsTest extends TestCase
{
    /** @return array<string, array<string, mixed>> */
    private function options(string $class): array
    {
        $built = new CommandBuilderFactory()->payloadFor($this->definition($class));

        return array_column($built['options'], null, 'name');
    }

    public function test_a_map_of_choices_uses_its_keys_as_labels(): void
    {
        $this->assertSame(
            [
                ['name' => 'Small', 'value' => 's'],
                ['name' => 'Large', 'value' => 'l'],
            ],
            $this->options(ConstrainedCommand::class)['size']['choices'],
        );
    }

    /**
     * A list has no labels of its own, so each value stands in as its own.
     */
    public function test_a_list_of_choices_labels_each_value_with_itself(): void
    {
        $this->assertSame(
            [
                ['name' => 'red', 'value' => 'red'],
                ['name' => 'green', 'value' => 'green'],
            ],
            $this->options(ConstrainedCommand::class)['colour']['choices'],
        );
    }

    public function test_numeric_bounds_reach_the_payload(): void
    {
        $count = $this->options(ConstrainedCommand::class)['count'];

        $this->assertSame(1, $count['min_value']);
        $this->assertSame(10, $count['max_value']);
    }

    public function test_string_bounds_reach_the_payload(): void
    {
        $note = $this->options(ConstrainedCommand::class)['note'];

        $this->assertSame(2, $note['min_length']);
        $this->assertSame(32, $note['max_length']);
    }

    public function test_channel_types_reach_the_payload(): void
    {
        $this->assertSame(
            [ChannelType::GUILD_TEXT->value],
            $this->options(ConstrainedCommand::class)['channel']['channel_types'],
        );
    }

    /**
     * An option that declares no constraints must not carry empty ones.
     */
    public function test_an_unconstrained_option_carries_no_constraint_keys(): void
    {
        $name = $this->options(PingCommand::class)['name'];

        foreach (['choices', 'min_value', 'max_value', 'min_length', 'max_length', 'channel_types'] as $key) {
            $this->assertArrayNotHasKey($key, $name);
        }
    }
}
