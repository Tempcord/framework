<?php

namespace Tempcord\Tests\Unit\Discord;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\InteractionData;
use Tempcord\Discord\AllCommandExtension;
use ReflectionMethod;

#[CoversClass(AllCommandExtension::class)]
final class AllCommandExtensionTest extends BaseTestCase
{
    private function option(string $name, ApplicationCommandOptionType $type, array $children = []): ApplicationCommandInteractionDataOptionStructure
    {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = $name;
        $option->type = $type;
        $option->options = $children;

        return $option;
    }

    private function fullName(InteractionCreate $interaction): string
    {
        return new ReflectionMethod(AllCommandExtension::class, 'getFullNameByInteraction')
            ->invoke(new AllCommandExtension(), $interaction);
    }

    private function interaction(string $command, array $options): InteractionCreate
    {
        $data = new InteractionData();
        $data->name = $command;
        $data->options = $options;

        $interaction = new InteractionCreate();
        $interaction->data = $data;

        return $interaction;
    }

    public function test_a_plain_command_resolves_to_its_own_name(): void
    {
        $interaction = $this->interaction('ping', [
            $this->option('name', ApplicationCommandOptionType::STRING),
        ]);

        $this->assertSame('ping', $this->fullName($interaction));
    }

    public function test_a_subcommand_is_appended_to_the_command_name(): void
    {
        $interaction = $this->interaction('moderation', [
            $this->option('kick', ApplicationCommandOptionType::SUB_COMMAND, [
                $this->option('reason', ApplicationCommandOptionType::STRING),
            ]),
        ]);

        $this->assertSame('moderation.kick', $this->fullName($interaction));
    }

    public function test_it_drills_through_a_subcommand_group(): void
    {
        $interaction = $this->interaction('music', [
            $this->option('playlist', ApplicationCommandOptionType::SUB_COMMAND_GROUP, [
                $this->option('play', ApplicationCommandOptionType::SUB_COMMAND, [
                    $this->option('title', ApplicationCommandOptionType::STRING),
                ]),
            ]),
        ]);

        $this->assertSame('music.playlist.play', $this->fullName($interaction));
    }

    public function test_a_command_without_options_resolves_to_its_own_name(): void
    {
        $interaction = $this->interaction('stop', []);

        $this->assertSame('stop', $this->fullName($interaction));
    }
}
