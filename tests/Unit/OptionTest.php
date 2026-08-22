<?php

namespace Tempcord\Tests\Unit;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Tests\Fixtures\OptionTypesCommand;
use Tempest\Reflection\ClassReflector;

#[CoversClass(Option::class)]
final class OptionTest extends TestCase
{
    /**
     * @return array<string, Option>
     */
    private function optionsOf(string $method): array
    {
        $reflector = new ClassReflector(OptionTypesCommand::class)->getMethod($method);

        /** @var Subcommand $subcommand */
        $subcommand = $reflector->getAttribute(Subcommand::class);
        $subcommand->reflector = $reflector;

        return $subcommand->options;
    }

    public static function supportedTypes(): array
    {
        return [
            'string'  => ['text', ApplicationCommandOptionType::STRING],
            'int'     => ['count', ApplicationCommandOptionType::INTEGER],
            'float'   => ['ratio', ApplicationCommandOptionType::NUMBER],
            'bool'    => ['flag', ApplicationCommandOptionType::BOOLEAN],
            'User'    => ['user', ApplicationCommandOptionType::USER],
            'Channel' => ['channel', ApplicationCommandOptionType::CHANNEL],
            'Role'    => ['role', ApplicationCommandOptionType::ROLE],
        ];
    }

    #[DataProvider('supportedTypes')]
    public function test_it_maps_php_types_to_discord_option_types(string $parameter, ApplicationCommandOptionType $expected): void
    {
        $this->assertSame($expected, $this->optionsOf('all')[$parameter]->type);
    }

    public function test_it_rejects_an_unsupported_type(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Command option type not supported');

        $this->optionsOf('unsupported')['values']->type;
    }

    public function test_it_rejects_an_untyped_parameter(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Command option does not have type');

        $this->optionsOf('untyped')['whatever']->type;
    }

    public function test_the_name_defaults_to_the_parameter_name(): void
    {
        $this->assertSame('text', $this->optionsOf('all')['text']->name);
    }

    public function test_an_explicit_name_wins_over_the_parameter_name(): void
    {
        $this->assertSame('custom', $this->optionsOf('renamed')['original']->name);
    }

    public function test_a_parameter_without_a_default_is_required(): void
    {
        $this->assertTrue($this->optionsOf('all')['text']->isRequired);
    }

    public function test_it_builds_a_command_option(): void
    {
        $built = $this->optionsOf('all')['count']->build->get();

        $this->assertSame('count', $built['name']);
        $this->assertSame('an int', $built['description']);
        $this->assertTrue($built['required']);
        $this->assertSame(ApplicationCommandOptionType::INTEGER->value, $built['type']);
        $this->assertFalse($built['autocomplete']);
    }
}
