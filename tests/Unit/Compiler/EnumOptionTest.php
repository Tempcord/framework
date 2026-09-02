<?php

namespace Tempcord\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Discord\Enums\ApplicationCommandOptionType;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use Tempcord\Discord\Parts\InteractionData;
use Tempcord\Interfaces\Choosable;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\Platform;
use Tempcord\Tests\Fixtures\PlatformCommand;
use Tempcord\Tests\Fixtures\Region;
use Tempcord\Tests\Unit\TestCase;
use RuntimeException;

/**
 * An option typed as a backed enum.
 *
 * A fixed set of values written as a string plus a hand kept choice list is two
 * places to change and one to forget. The framework already resolves enums for
 * a component's arguments; a command's options were the one place it did not.
 */
#[CoversClass(CommandCompiler::class)]
#[CoversClass(OptionValueResolver::class)]
final class EnumOptionTest extends TestCase
{
    private function option(string $name): object
    {
        return $this->definition(PlatformCommand::class)->options[$name];
    }

    public function test_a_string_backed_enum_is_a_string_option(): void
    {
        $this->assertSame(ApplicationCommandOptionType::STRING, $this->option('platform')->type);
    }

    public function test_an_int_backed_enum_is_an_integer_option(): void
    {
        $this->assertSame(ApplicationCommandOptionType::INTEGER, $this->option('region')->type);
    }

    /**
     * The whole point of reaching for an enum: the cases are the choices, so
     * there is no second list to keep in step with it.
     */
    public function test_every_case_is_offered_as_a_choice(): void
    {
        $this->assertSame(
            ['PC' => 'PC', 'PlayStation' => 'PS4', 'Xbox' => 'X1'],
            $this->option('platform')->choices,
        );
    }

    /**
     * A case name is a PHP identifier and rarely what a bot wants shown, so an
     * enum can say how each case reads.
     */
    public function test_a_choosable_enum_names_its_own_cases(): void
    {
        $this->assertContains(Choosable::class, class_implements(Platform::class));
        $this->assertArrayHasKey('PlayStation', $this->option('platform')->choices);
    }

    /**
     * Without that, the case name is used — at least a name somebody chose.
     */
    public function test_a_plain_enum_falls_back_to_its_case_names(): void
    {
        $this->assertSame(
            ['Europe' => 1, 'NorthAmerica' => 2],
            $this->option('region')->choices,
        );
    }

    private function resolve(string $value, string $option = 'platform'): mixed
    {
        $structure = new ApplicationCommandInteractionDataOptionStructure();
        $structure->name = $option;
        $structure->type = $this->option($option)->type;
        $structure->value = $value;

        $data = new InteractionData();
        $data->name = 'platform';
        $data->options = [$structure];

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = $data;

        return new OptionValueResolver(new FakeDiscord(new RecordingHttp()))->resolve(
            $structure,
            new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp())),
            $this->option($option)->parameter(),
        );
    }

    public function test_the_handler_is_given_the_case_rather_than_the_value(): void
    {
        $this->assertSame(Platform::PlayStation, $this->resolve('PS4'));
    }

    public function test_an_int_backed_case_is_found_by_its_number(): void
    {
        $this->assertSame(Region::NorthAmerica, $this->resolve('2', 'region'));
    }

    /**
     * Discord checks a choice against the list it was given, so a value that is
     * not a case can only come from a client that made one up. Saying which
     * option and which value beats a ValueError from deep inside from().
     */
    public function test_a_value_that_is_not_a_case_is_reported_clearly(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not a case of');

        $this->resolve('Nintendo');
    }
}
