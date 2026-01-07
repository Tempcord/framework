<?php
declare(strict_types=1);

namespace Tempcord {

    use Tempcord\Support\Components\ActionRowBuilder;
    use Tempcord\Support\Components\ButtonBuilder;
    use Tempcord\Support\Components\ModalBuilder;
    use Tempcord\Support\Components\SelectMenuBuilder;
    use Tempcord\Support\Components\TextInputBuilder;
    use Tempcord\Support\DiscordObjectFactory;
    use function Tempest\get;

    function discord(mixed $value): DiscordObjectFactory
    {
        return get(DiscordObjectFactory::class)?->withData($value);
    }

    // ============ Component Helper Functions ============

    /**
     * Create a primary button
     */
    function primaryButton(string $label, string $customId): ButtonBuilder
    {
        return ButtonBuilder::primary($label, $customId);
    }

    /**
     * Create a secondary button
     */
    function secondaryButton(string $label, string $customId): ButtonBuilder
    {
        return ButtonBuilder::secondary($label, $customId);
    }

    /**
     * Create a success button
     */
    function successButton(string $label, string $customId): ButtonBuilder
    {
        return ButtonBuilder::success($label, $customId);
    }

    /**
     * Create a danger button
     */
    function dangerButton(string $label, string $customId): ButtonBuilder
    {
        return ButtonBuilder::danger($label, $customId);
    }

    /**
     * Create a link button
     */
    function linkButton(string $label, string $url): ButtonBuilder
    {
        return ButtonBuilder::link($label, $url);
    }

    /**
     * Create a string select menu
     */
    function stringSelect(string $customId): SelectMenuBuilder
    {
        return SelectMenuBuilder::string($customId);
    }

    /**
     * Create a user select menu
     */
    function userSelect(string $customId): SelectMenuBuilder
    {
        return SelectMenuBuilder::user($customId);
    }

    /**
     * Create a role select menu
     */
    function roleSelect(string $customId): SelectMenuBuilder
    {
        return SelectMenuBuilder::role($customId);
    }

    /**
     * Create a channel select menu
     */
    function channelSelect(string $customId): SelectMenuBuilder
    {
        return SelectMenuBuilder::channel($customId);
    }

    /**
     * Create a modal
     */
    function modal(string $customId, string $title): ModalBuilder
    {
        return ModalBuilder::create($customId, $title);
    }

    /**
     * Create a short text input for modals
     */
    function shortInput(string $customId, string $label): TextInputBuilder
    {
        return TextInputBuilder::short($customId, $label);
    }

    /**
     * Create a paragraph text input for modals
     */
    function paragraphInput(string $customId, string $label): TextInputBuilder
    {
        return TextInputBuilder::paragraph($customId, $label);
    }

    /**
     * Create an action row
     */
    function actionRow(): ActionRowBuilder
    {
        return ActionRowBuilder::create();
    }
}