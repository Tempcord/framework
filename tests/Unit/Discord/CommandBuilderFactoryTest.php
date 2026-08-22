<?php

namespace Tempcord\Tests\Unit\Discord;

use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Tests\Fixtures\BareCommand;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\OptionTypesCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(CommandBuilderFactory::class)]
final class CommandBuilderFactoryTest extends TestCase
{
    private CommandBuilderFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new CommandBuilderFactory();
    }

    /** @return array<string, mixed> */
    private function build(string $class): array
    {
        return $this->factory->forCommand($this->definition($class))->get();
    }

    public function test_it_builds_a_chat_input_command(): void
    {
        $builder = $this->factory->forCommand($this->definition(PingCommand::class));
        $built = $builder->get();

        $this->assertSame('ping', $built['name']);
        $this->assertSame('Replies with pong', $built['description']);
        $this->assertFalse($builder->getNsfw());
        $this->assertTrue($builder->getDmPermission());
    }

    public function test_a_command_without_options_builds_cleanly(): void
    {
        $built = $this->build(BareCommand::class);

        $this->assertSame('bare', $built['name']);
        $this->assertSame([], $built['options'] ?? []);
    }

    public function test_it_builds_each_invoke_parameter_as_an_option(): void
    {
        $built = $this->build(PingCommand::class);

        $this->assertSame(['name', 'times'], array_column($built['options'], 'name'));
        $this->assertTrue($built['options'][0]['required']);
        $this->assertFalse($built['options'][1]['required']);
    }

    public function test_an_option_carries_its_type_and_description(): void
    {
        $option = $this->build(OptionTypesCommand::class)['options'][0]['options'][1];

        $this->assertSame('count', $option['name']);
        $this->assertSame('an int', $option['description']);
        $this->assertSame(ApplicationCommandOptionType::INTEGER->value, $option['type']);
        $this->assertTrue($option['required']);
        $this->assertFalse($option['autocomplete']);
    }

    public function test_an_ungrouped_command_builds_its_subcommands_directly(): void
    {
        $options = $this->build(ModerationCommand::class)['options'];

        $this->assertCount(1, $options);
        $this->assertSame('kick', $options[0]['name']);
        $this->assertSame(ApplicationCommandOptionType::SUB_COMMAND->value, $options[0]['type']);
        $this->assertSame('reason', $options[0]['options'][0]['name']);
    }

    public function test_a_grouped_command_nests_its_subcommands_under_the_group(): void
    {
        $options = $this->build(MusicCommand::class)['options'];

        $this->assertCount(1, $options);
        $this->assertSame('playlist', $options[0]['name']);
        $this->assertSame('Playlist controls', $options[0]['description']);
        $this->assertSame(ApplicationCommandOptionType::SUB_COMMAND_GROUP->value, $options[0]['type']);

        $this->assertSame(['play', 'stop'], array_column($options[0]['options'], 'name'));
        $this->assertSame(
            ApplicationCommandOptionType::SUB_COMMAND->value,
            $options[0]['options'][0]['type'],
        );
        $this->assertSame('title', $options[0]['options'][0]['options'][0]['name']);
    }

    public function test_a_subcommand_without_parameters_carries_no_options(): void
    {
        $stop = $this->build(MusicCommand::class)['options'][0]['options'][1];

        $this->assertSame('stop', $stop['name']);
        $this->assertSame([], $stop['options'] ?? []);
    }
}
