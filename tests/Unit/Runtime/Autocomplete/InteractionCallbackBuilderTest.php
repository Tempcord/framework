<?php

namespace Tempcord\Tests\Unit\Runtime\Autocomplete;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Discord\Enums\InteractionCallbackType;
use Tempcord\Discord\Parts\ApplicationCommandOptionChoice;
use Tempcord\Runtime\Autocomplete\InteractionCallbackBuilder;

#[CoversClass(InteractionCallbackBuilder::class)]
final class InteractionCallbackBuilderTest extends BaseTestCase
{
    private function choice(string $name, string $value): ApplicationCommandOptionChoice
    {
        $choice = new ApplicationCommandOptionChoice();
        $choice->name = $name;
        $choice->value = $value;

        return $choice;
    }

    /**
     * No suggestions is still an answer: Discord requires data.choices on an
     * autocomplete result and rejects one without it (50035, BASE_TYPE_REQUIRED).
     */
    public function test_it_sends_an_empty_choice_list_when_there_are_no_suggestions(): void
    {
        $data = InteractionCallbackBuilder::new()
            ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT)
            ->get();

        $this->assertSame([], $data['data']['choices']);
    }

    public function test_it_adds_choices_to_the_callback_payload(): void
    {
        $choices = [$this->choice('Apple', 'apple')];

        $data = InteractionCallbackBuilder::new()
            ->setChoices($choices)
            ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT)
            ->get();

        $this->assertSame(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT->value, $data['type']);
        $this->assertSame($choices, $data['data']['choices']);
    }

    public function test_set_choices_is_fluent_and_replaces_previous_choices(): void
    {
        $builder = InteractionCallbackBuilder::new();
        $builder->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT);

        $second = [$this->choice('Banana', 'banana')];

        $this->assertSame($builder, $builder->setChoices([$this->choice('Apple', 'apple')]));
        $this->assertSame($second, $builder->setChoices($second)->get()['data']['choices']);
    }
}
