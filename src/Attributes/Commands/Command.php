<?php

namespace Tempcord\Attributes\Commands;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use Tempcord\Attributes\HasSubcommands;
use Tempcord\Contract\Buildable;
use Tempcord\Contract\CanBeHandled;
use Tempcord\Middleware\CommandMiddleware;
use Tempcord\Support\Commands\CommandHandler;
use Tempcord\Support\Localization\CommandTranslator;
use Tempcord\Support\Traits\HasAttributes;
use Tempcord\TempcordConfig;
use Tempest\Reflection\ClassReflector;
use function Tempest\get;
use function Tempest\Support\arr;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_CLASS)]
final class Command implements Buildable, CanBeHandled
{
    use HasAttributes, HasSubcommands;

    public ClassReflector $reflector;

    /**
     * Command name
     *
     * If none is provided from constructor - name would be created from the class name,
     * removing "Command" from the beginning and end of the class name.
     * Additionally, it will be snake_case.
     */
    public string $name {
        get {
            return $this->rememberAttribute('name', fn() => str($this->reflector->getShortName())
                ->replaceEnd('Command', '')
                ->replaceStart('Command', '')
                ->snake('_')
                ->lower()
                ->toString());
        }
    }

    /**
     * Command's handler
     *
     * @var CommandHandler
     */
    public CommandHandler $handler {
        get {
            $method = null;

            $publicMethods = arr($this->reflector->getPublicMethods());
            if ($publicMethods->count() === 1 && $publicMethods->first()->getName() !== '__construct') {
                $method = $this->reflector->getPublicMethods()[0];
            }

            if ($this->reflector->getReflection()->hasMethod('__invoke')) {
                $method = $this->reflector->getMethod('__invoke');
            }

            return new CommandHandler($this, $method, get(TempcordConfig::class));
        }
    }

    /**
     * Build command options
     *
     * Options should include any handler options defined by user
     * If command is grouped - then we return only SubcommandGroups.
     * If command has subcommands and is not grouped - then we return all subcommands
     *
     * Options can be merged (if another command with the same base name is defined in another class)
     *
     * @var array<Option|Subcommand>
     */
    public array $options {
        get {
            $options = [];

            if ($this->hasSubcommands) {
                $options = $this->subcommands;
            } else {
                foreach ($this->handler->method?->getParameters() as $parameter) {
                    if ($parameter->hasAttribute(Option::class)) {
                        $options[] = $parameter->getAttribute(Option::class)
                            ?->withReflector($parameter);
                    }
                }
            }

            return $options;
        }
    }

    /**
     * Get the translation key for this command
     */
    public string $translationKey {
        get {
            return $this->rememberAttribute('translationKey', fn() => $this->name);
        }
    }

    /**
     * The CommandBuilder
     *
     * This CommandBuilder allows us to build the command and send it to discord
     */
    public CommandBuilder $builder {
        get {
            $builder = CommandBuilder::new()
                ->setName($this->name)
                ->setNsfw($this->isNsfw)
                ->setDmPermission($this->directMessage)
                ->setType($this->type)
                ->setDescription($this->description);

            // Add localizations if enabled
            if ($this->localized) {
                $this->applyLocalizations($builder);
            }

            foreach ($this->options as $option) {
                // Pass localization context to options
                if ($this->localized && $option instanceof Option) {
                    $option->setLocalizationContext($this->translationKey, $this->localized);
                }
                $builder->addOption($option->builder);
            }

            return $builder;
        }
    }

    /**
     * Apply localizations to the command builder
     */
    private function applyLocalizations(CommandBuilder $builder): void
    {
        try {
            $translator = get(CommandTranslator::class);
            $localizations = $translator->getCommandLocalizations($this->translationKey);

            $nameLocalizations = [];
            $descriptionLocalizations = [];

            foreach ($localizations as $locale => $translation) {
                if (isset($translation['name'])) {
                    $nameLocalizations[$locale] = $translation['name'];
                }
                if (isset($translation['description'])) {
                    $descriptionLocalizations[$locale] = $translation['description'];
                }
            }

            if (!empty($nameLocalizations)) {
                $builder->setNameLocalizations($nameLocalizations);
            }

            if (!empty($descriptionLocalizations)) {
                $builder->setDescriptionLocalizations($descriptionLocalizations);
            }
        } catch (\Throwable) {
            // Silently ignore if translator is not available
        }
    }

    /**
     * @param string|BackedEnum|null $name Command name
     * @param string|null $description Command description
     * @param int|null $guildId Guild ID for guild-specific commands
     * @param bool $isNsfw Whether the command is NSFW
     * @param array $permissions Required permissions
     * @param bool $directMessage Whether command works in DMs
     * @param ApplicationCommandTypes $type Command type
     * @param array<class-string<CommandMiddleware>> $middleware Middleware classes
     * @param bool $localized Enable localization support
     * @param string|null $translationKey Custom translation key (defaults to command name)
     */
    public function __construct(
        string|BackedEnum|null         $name = null,
        public ?string                 $description = null,
        public ?int                    $guildId = null,
        public bool                    $isNsfw = false,
        public array                   $permissions = [],
        public bool                    $directMessage = true,
        public ApplicationCommandTypes $type = ApplicationCommandTypes::CHAT_INPUT,
        public array                   $middleware = [],
        public bool                    $localized = false,
        ?string                        $translationKey = null,
    )
    {
        $this->setAttribute('name', $name);
        $this->setAttribute('translationKey', $translationKey);

        if (($this->type === ApplicationCommandTypes::CHAT_INPUT) && $this->description === null) {
            throw new \InvalidArgumentException("Description for command is required when type=CHAT_INPUT");
        }
    }

    public function withReflector(ClassReflector $class): Command
    {
        $this->reflector = $class;
        return $this;
    }
}