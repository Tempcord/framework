<?php

namespace Tempcord\Tests\Unit\Discord;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use CyberWolf\Discord\Enums\InteractionCallbackType;
use CyberWolf\Discord\Parts\ApplicationCommandOptionChoice;
use Tempcord\Discord\InteractionCallbackBuilder;

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

    public function test_it_omits_the_choices_key_when_none_were_set(): void
    {
        $data = InteractionCallbackBuilder::new()
            ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT)
            ->get();

        $this->assertArrayNotHasKey('choices', $data['data'] ?? []);
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
