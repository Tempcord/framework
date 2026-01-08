<?php

namespace Tempcord\Attributes\Prompts;

use Attribute;
use BackedEnum;
use Tempcord\Support\Prompts\PromptHandler;
use Tempcord\Support\Traits\HasAttributes;
use Tempest\Reflection\ClassReflector;
use function Tempest\Support\arr;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_CLASS)]
final class Prompt
{
    use HasAttributes;

    public ClassReflector $reflector;

    /**
     * Prompt name
     *
     * If none is provided from constructor - name would be created from the class name,
     * removing "Prompt" from the beginning and end of the class name.
     * Additionally, it will be snake_case.
     */
    public string $name {
        get {
            return $this->rememberAttribute('name', fn() => str($this->reflector->getShortName())
                ->replaceEnd('Prompt', '')
                ->replaceStart('Prompt', '')
                ->snake('_')
                ->lower()
                ->toString());
        }
    }

    /**
     * Prompt's handler
     *
     * @var PromptHandler
     */
    public PromptHandler $handler {
        get {
            $method = null;

            $publicMethods = arr($this->reflector->getPublicMethods());
            if ($publicMethods->count() === 1 && $publicMethods->first()->getName() !== '__construct') {
                $method = $this->reflector->getPublicMethods()[0];
            }

            if ($this->reflector->getReflection()->hasMethod('__invoke')) {
                $method = $this->reflector->getMethod('__invoke');
            }

            return new PromptHandler($this, $method);
        }
    }

    /**
     * Prompt options extracted from handler parameters
     *
     * @var array<PromptOption>
     */
    public array $options {
        get {
            $options = [];

            foreach ($this->handler->method?->getParameters() as $parameter) {
                if ($parameter->hasAttribute(PromptOption::class)) {
                    $options[] = $parameter->getAttribute(PromptOption::class)
                        ?->withReflector($parameter);
                }
            }

            return $options;
        }
    }

    /**
     * @param string|BackedEnum|null $name Prompt name
     * @param string|null $description Prompt description
     * @param array<string> $aliases Prompt aliases (e.g., ['cls'] for 'clear')
     */
    public function __construct(
        string|BackedEnum|null $name = null,
        public ?string         $description = null,
        public array           $aliases = [],
    )
    {
        $this->setAttribute('name', $name);
    }

    public function withReflector(ClassReflector $class): Prompt
    {
        $this->reflector = $class;
        return $this;
    }
}
