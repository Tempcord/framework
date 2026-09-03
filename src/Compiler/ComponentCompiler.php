<?php

namespace Tempcord\Compiler;

use BackedEnum;
use RuntimeException;
use Tempcord\Attributes\Button;
use Tempcord\Attributes\ModalSubmit;
use Tempcord\Attributes\SelectMenu;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Enums\ComponentKind;
use Tempcord\Runtime\CustomId;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;
use function Tempest\Support\str;

/**
 * Turns #[Button], #[SelectMenu] and #[ModalSubmit] into ComponentDefinitions.
 *
 * All three declare the same thing — a custom id and the callable that answers
 * it — so one compiler handles them and the attribute only decides the kind.
 */
final readonly class ComponentCompiler
{
    /**
     * @var array<class-string, ComponentKind>
     */
    private const array ATTRIBUTES = [
        Button::class => ComponentKind::Button,
        SelectMenu::class => ComponentKind::SelectMenu,
        ModalSubmit::class => ComponentKind::ModalSubmit,
    ];

    /**
     * Class name affixes that describe the kind rather than the component, and
     * so are dropped when a name is derived rather than given.
     *
     * @var array<string, list<string>>
     */
    private const array AFFIXES = [
        'button' => ['Button'],
        'select_menu' => ['SelectMenu', 'Menu'],
        'modal_submit' => ['ModalSubmit', 'Modal'],
    ];

    /**
     * Every component handler declared on the class, class-level and
     * method-level alike.
     *
     * @return list<ComponentDefinition>
     */
    public function compile(ClassReflector $class): array
    {
        $definitions = [];

        foreach (self::ATTRIBUTES as $attribute => $kind) {
            foreach ($class->getAttributes($attribute) as $declaration) {
                $definitions[] = $this->definition(
                    $class,
                    $this->invokerOf($class, $kind),
                    $kind,
                    $declaration->id,
                    $declaration->middleware,
                );
            }

            foreach ($class->getPublicMethods() as $method) {
                foreach ($method->getAttributes($attribute) as $declaration) {
                    $definitions[] = $this->definition(
                        $class,
                        $method,
                        $kind,
                        $declaration->id,
                        $declaration->middleware,
                    );
                }
            }
        }

        return $definitions;
    }

    /**
     * @param array<mixed> $middleware
     */
    private function definition(
        ClassReflector $class,
        MethodReflector $method,
        ComponentKind $kind,
        string|BackedEnum|null $id,
        array $middleware,
    ): ComponentDefinition {
        $customId = CustomId::compile($this->idOf($class, $method, $kind, $id));

        return new ComponentDefinition(
            kind: $kind,
            customId: $customId,
            handler: $class->getName(),
            method: $method,
            middleware: DeclaredMiddleware::checked(
                $middleware,
                ucfirst($kind->value) . ' "' . $customId->pattern . '"',
            ),
        );
    }

    /**
     * An explicit id wins. Otherwise a named method carries the id, and
     * __invoke hands the job back to the class name.
     */
    private function idOf(
        ClassReflector $class,
        MethodReflector $method,
        ComponentKind $kind,
        string|BackedEnum|null $id,
    ): string {
        if ($id !== null) {
            return $id instanceof BackedEnum ? (string) $id->value : $id;
        }

        if ($method->getName() !== '__invoke') {
            return str($method->getName())->snake('_')->lower()->toString();
        }

        $name = str($class->getShortName());

        foreach (self::AFFIXES[$kind->value] as $affix) {
            $name = $name->replaceEnd($affix, '')->replaceStart($affix, '');
        }

        return $name->snake('_')->lower()->toString();
    }

    private function invokerOf(ClassReflector $class, ComponentKind $kind): MethodReflector
    {
        if (!$class->getReflection()->hasMethod('__invoke')) {
            throw new RuntimeException(
                'Class [' . $class->getName() . '] carries a ' . $kind->value
                . ' attribute, so it should declare an __invoke method or move the attribute onto a method',
            );
        }

        return $class->getMethod('__invoke');
    }
}
