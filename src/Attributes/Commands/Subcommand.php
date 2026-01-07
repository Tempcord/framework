<?php

namespace Tempcord\Attributes\Commands;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use Tempcord\Support\Localization\CommandTranslator;
use Tempcord\Support\Traits\HasAttributes;
use Tempest\Reflection\MethodReflector;
use function Tempest\get;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_METHOD)]
final class Subcommand
{
    use HasAttributes;

    public ?MethodReflector $reflector = null;

    public string $name {
        get {
            return $this->rememberAttribute('name', fn() => str($this->reflector->getName())
                ->snake('_')
                ->lower()
                ->toString());
        }
    }

    /**
     * @var array<string, Option>
     */
    public array $options {
        get {
            $options = [];
            foreach ($this->reflector->getParameters() as $parameter) {
                if ($parameter->hasAttribute(Option::class)) {
                    /** @var Option $option */
                    $option = $parameter->getAttribute(Option::class);
                    $option->reflector = $parameter;
                    $options[$parameter->getName()] = $option;
                }
            }
            return $options;
        }
    }

    public CommandOptionBuilder $builder {
        get {
            /** @var CommandTranslator $translator */
            $translator = get(CommandTranslator::class);
            $subcommand = new CommandOptionBuilder()
                ->setName($this->name)
                ->setDescription($translator->resolve($this->description) ?? $this->description)
                ->setType(ApplicationCommandOptionType::SUB_COMMAND);

            foreach ($this->options as $option) {
                $subcommand->addOption($option->builder);
            }

            // Auto-add localizations if description is a translation key
            if ($translator->isTranslationKey($this->description)) {
                $descLocalizations = $translator->getLocalizations($this->description);
                if (!empty($descLocalizations)) {
                    $subcommand->setDescriptionLocalizations($descLocalizations);
                }
            }

            return $subcommand;
        }
    }

    public function __construct(
        public string          $description,
        string|BackedEnum|null $name = null,
        public array           $middleware = [],
    )
    {
        $this->setAttribute('name', $name);
    }

    public function withReflector(MethodReflector $reflector): Subcommand
    {
        $this->reflector = $reflector;
        return $this;
    }
}