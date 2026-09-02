<?php

namespace Tempcord\Tools;

use ReflectionClass;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Reads the public API out of the source, so the reference cannot describe a
 * framework that does not exist.
 */
final readonly class ApiReflector
{
    /**
     * What a bot author actually writes. Everything else — the compiler, the
     * definitions, the runtime — is internal and deliberately undocumented.
     *
     * @var array<string, list<class-string>>
     */
    private const array SURFACE = [
        'attributes' => [
            \Tempcord\Attributes\Command::class,
            \Tempcord\Attributes\SubcommandGroup::class,
            \Tempcord\Attributes\Subcommand::class,
            \Tempcord\Attributes\Option::class,
            \Tempcord\Attributes\Event::class,
            \Tempcord\Attributes\Autocomplete::class,
            \Tempcord\Attributes\Button::class,
            \Tempcord\Attributes\SelectMenu::class,
            \Tempcord\Attributes\ModalSubmit::class,
            \Tempcord\Attributes\Scheduled::class,
        ],
        'autocomplete' => [
            \Tempcord\Interfaces\Autocomplete::class,
            \Tempcord\AutoCompletes\ArrayAutocomplete::class,
        ],
        'cache' => [
            \Tempcord\Cache\Cache::class,
        ],
        'components' => [
            \Tempcord\Runtime\CustomId::class,
        ],
        'configuration' => [
            \Tempcord\TempcordConfig::class,
        ],
        'enums' => [
            \Tempcord\Enums\DiscordLocale::class,
        ],
        'plugins' => [
            \Tempcord\Plugins\Plugin::class,
        ],
    ];

    /**
     * @return array<string, list<Symbol>>
     */
    public function reflect(): array
    {
        $groups = [];

        foreach (self::SURFACE as $group => $classes) {
            foreach ($classes as $class) {
                $groups[$group][] = $this->symbolFor($group, $class);
            }
        }

        return $groups;
    }

    private function symbolFor(string $group, string $class): Symbol
    {
        $reflection = new ReflectionClass($class);

        return new Symbol(
            name: $reflection->getShortName(),
            fqcn: $class,
            kind: $this->kindOf($reflection),
            summary: $this->summarize($reflection->getDocComment() ?: ''),
            slug: 'reference/' . $group . '/' . $this->slugify($reflection->getShortName()),
            parameters: $this->parametersOf($reflection),
            cases: $this->casesOf($reflection),
            methods: $this->methodsOf($reflection),
            target: $this->targetOf($reflection),
        );
    }

    private function kindOf(ReflectionClass $reflection): string
    {
        return match (true) {
            $reflection->isEnum() => 'enum',
            $reflection->isInterface() => 'interface',
            $reflection->getAttributes(\Attribute::class) !== [] => 'attribute',
            default => 'class',
        };
    }

    /**
     * Which declarations an attribute may be written on, read from the
     * #[Attribute] flags rather than from prose.
     */
    private function targetOf(ReflectionClass $reflection): ?string
    {
        $attribute = $reflection->getAttributes(\Attribute::class)[0] ?? null;

        if ($attribute === null) {
            return null;
        }

        $flags = $attribute->getArguments()[0] ?? \Attribute::TARGET_ALL;

        $targets = [
            \Attribute::TARGET_CLASS => 'class',
            \Attribute::TARGET_METHOD => 'method',
            \Attribute::TARGET_PARAMETER => 'parameter',
            \Attribute::TARGET_PROPERTY => 'property',
            \Attribute::TARGET_FUNCTION => 'function',
            \Attribute::TARGET_CLASS_CONSTANT => 'class constant',
        ];

        $matched = [];

        foreach ($targets as $flag => $label) {
            if (($flags & $flag) === $flag) {
                $matched[] = $label;
            }
        }

        return $matched === [] ? null : implode(', ', $matched);
    }

    /**
     * @return list<Parameter>
     */
    private function parametersOf(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $descriptions = $this->paramDescriptions($constructor->getDocComment() ?: '');
        $parameters = [];

        foreach ($constructor->getParameters() as $parameter) {
            $parameters[] = new Parameter(
                name: $parameter->getName(),
                type: $this->renderType($parameter->getType()),
                default: $this->defaultOf($parameter),
                summary: $descriptions[$parameter->getName()] ?? '',
            );
        }

        return $parameters;
    }

    /**
     * @return list<array{name: string, value: string, note: string}>
     */
    private function casesOf(ReflectionClass $reflection): array
    {
        if (!$reflection->isEnum()) {
            return [];
        }

        $cases = [];

        foreach (new ReflectionEnum($reflection->getName())->getCases() as $case) {
            /*
             * A pure enum has no value to show, so only backed cases carry one.
             */
            $value = $case instanceof ReflectionEnumBackedCase ? $case->getBackingValue() : '';

            $cases[] = [
                'name' => $case->getName(),
                'value' => is_string($value) ? $value : (string) $value,
                'note' => '',
            ];
        }

        return $cases;
    }

    /**
     * @return list<array{signature: string, summary: string}>
     */
    private function methodsOf(ReflectionClass $reflection): array
    {
        if (!$reflection->isInterface() && !$reflection->isEnum()) {
            return [];
        }

        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() && $reflection->isEnum()) {
                continue;
            }

            $parameters = array_map(
                fn(ReflectionParameter $p) => $this->renderType($p->getType()) . ' $' . $p->getName(),
                $method->getParameters(),
            );

            $methods[] = [
                'signature' => $method->getName() . '(' . implode(', ', $parameters) . '): '
                    . $this->renderType($method->getReturnType()),
                'summary' => $this->summarize($method->getDocComment() ?: ''),
            ];
        }

        return $methods;
    }

    private function defaultOf(ReflectionParameter $parameter): ?string
    {
        if (!$parameter->isDefaultValueAvailable()) {
            return null;
        }

        $default = $parameter->getDefaultValue();

        return match (true) {
            $default === null => 'null',
            $default === [] => '[]',
            is_bool($default) => $default ? 'true' : 'false',
            is_string($default) => "'" . $default . "'",
            is_object($default) => new ReflectionClass($default)->getShortName() . '::' . ($default->name ?? ''),
            default => var_export($default, true),
        };
    }

    private function renderType(?ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map($this->renderType(...), $type->getTypes()));
        }

        if (!$type instanceof ReflectionNamedType) {
            return (string) $type;
        }

        $name = $type->getName();

        if (!$type->isBuiltin() && str_contains($name, '\\')) {
            $name = substr($name, strrpos($name, '\\') + 1);
        }

        return ($type->allowsNull() && $name !== 'mixed' && $name !== 'null' ? '?' : '') . $name;
    }

    /**
     * The first paragraph of a docblock, with the asterisks stripped.
     */
    private function summarize(string $docblock): string
    {
        $lines = [];

        foreach (explode("\n", $docblock) as $line) {
            $line = trim(preg_replace('#^\s*/?\*+/?#', '', $line) ?? '');

            if (str_starts_with($line, '@')) {
                break;
            }

            if ($line === '' && $lines !== []) {
                break;
            }

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return implode(' ', $lines);
    }

    /**
     * @return array<string, string>
     */
    private function paramDescriptions(string $docblock): array
    {
        $descriptions = [];

        // A type may contain spaces, as in array<string, int>, so it is matched lazily
        // up to the parameter name rather than as a run of non-whitespace.
        if (!preg_match_all('/@param\s+[^\n]+?\s+\$(\w+)\s+(.+?)(?=\n\s*\*\s*@|\n\s*\*\/)/s', $docblock, $matches, PREG_SET_ORDER)) {
            return $descriptions;
        }

        foreach ($matches as $match) {
            $text = preg_replace('#\s*\*\s*#', ' ', $match[2]) ?? '';
            $descriptions[$match[1]] = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        }

        return $descriptions;
    }

    private function slugify(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name) ?? $name);
    }
}
