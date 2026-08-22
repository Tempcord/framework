<?php

namespace Tempcord\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Parts\ApplicationCommandOptionChoice;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Tests\Unit\TestCase;

/**
 * Whatever an Autocomplete hands back has to become a list Discord accepts:
 * at most 25 ApplicationCommandOptionChoice objects.
 */
#[CoversClass(ChoiceFactory::class)]
final class ChoiceFactoryTest extends TestCase
{
    /** @return list<ApplicationCommandOptionChoice> */
    private function toChoices(mixed $value): array
    {
        return new ChoiceFactory()->from($value);
    }

    /** @return list<array{string, mixed}> */
    private function pairs(array $choices): array
    {
        return array_map(
            static fn(ApplicationCommandOptionChoice $choice) => [$choice->name, $choice->value],
            $choices,
        );
    }

    public function test_a_list_uses_each_entry_as_its_own_label(): void
    {
        $this->assertSame(
            [['red', 'red'], ['green', 'green']],
            $this->pairs($this->toChoices(['red', 'green'])),
        );
    }

    public function test_a_map_uses_its_keys_as_labels(): void
    {
        $this->assertSame(
            [['Red', 'r'], ['Green', 'g']],
            $this->pairs($this->toChoices(['Red' => 'r', 'Green' => 'g'])),
        );
    }

    public function test_an_already_built_choice_is_passed_through_untouched(): void
    {
        $choice = new ApplicationCommandOptionChoice();
        $choice->name = 'Prebuilt';
        $choice->value = 'p';

        $this->assertSame([$choice], $this->toChoices([$choice]));
    }

    /**
     * Discord rejects an autocomplete response carrying more than 25 choices,
     * so the cap has to actually survive into the returned list.
     */
    public function test_it_caps_the_list_at_twenty_five_choices(): void
    {
        $this->assertCount(25, $this->toChoices(range(1, 40)));
    }

    public function test_it_caps_a_map_at_twenty_five_choices(): void
    {
        $items = [];
        for ($i = 0; $i < 40; $i++) {
            $items['label' . $i] = $i;
        }

        $this->assertCount(25, $this->toChoices($items));
    }

    /**
     * An Autocomplete is free to return a bare scalar for a single suggestion.
     */
    public function test_a_single_scalar_becomes_one_choice(): void
    {
        $this->assertSame([['solo', 'solo']], $this->pairs($this->toChoices('solo')));
    }

    public function test_integer_labels_are_cast_to_strings(): void
    {
        $choices = $this->toChoices([7 => 'seven']);

        $this->assertSame([['7', 'seven']], $this->pairs($choices));
    }
}
