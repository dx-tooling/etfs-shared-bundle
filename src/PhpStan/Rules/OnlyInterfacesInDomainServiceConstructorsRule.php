<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that classes under App\\<Feature>\\Domain\\Service\\* only depend on interfaces
 * in their constructor parameter type declarations.
 *
 * @implements Rule<ClassMethod>
 */
final class OnlyInterfacesInDomainServiceConstructorsRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->toString() !== '__construct') {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $className                  = $classReflection->getName();
        $isDomainServiceByNamespace = str_contains($className, '\\Domain\\Service\\');
        $shortName                  = ($pos = strrpos($className, '\\')) === false ? $className : substr($className, $pos + 1);
        $isDomainServiceBySuffix    = str_ends_with($shortName, 'DomainService');
        if (!$isDomainServiceByNamespace && !$isDomainServiceBySuffix) {
            return [];
        }

        $declaringNamespace = ($pos = strrpos($className, '\\')) === false ? '' : substr($className, 0, $pos);

        $errors = [];

        foreach ($node->params as $param) {
            $type = $param->type;
            if ($type === null) {
                // Untyped params are allowed (could be value objects created internally)
                continue;
            }

            $namedTypes = $this->collectNamedTypes($type);

            foreach ($namedTypes as $namedType) {
                $typeName = ltrim($namedType->toString(), '\\');

                // Built-ins and special names
                $lower = strtolower($typeName);
                if (in_array($lower, [
                    'self', 'static', 'parent',
                    'array', 'callable', 'iterable', 'object',
                    'string', 'int', 'float', 'bool', 'mixed', 'never', 'void', 'null',
                ], true)) {
                    continue;
                }

                $candidates = $this->candidateClassNames($typeName, $declaringNamespace);

                // Prefer PHPStan reflection provider for accurate symbol kind detection
                $isInterface = false;
                foreach ($candidates as $fqcn) {
                    if ($this->reflectionProvider->hasClass($fqcn) && $this->reflectionProvider->getClass($fqcn)->isInterface()) {
                        $isInterface = true;
                        break;
                    }
                }
                if ($isInterface) {
                    continue;
                }

                $isConcreteClass = false;
                foreach ($candidates as $fqcn) {
                    if ($this->reflectionProvider->hasClass($fqcn) && !$this->reflectionProvider->getClass($fqcn)->isInterface()) {
                        $isConcreteClass = true;
                        $typeName        = $fqcn; // report fully-qualified name
                        break;
                    }
                }

                if ($isConcreteClass) {
                    $paramName = ($param->var instanceof Variable && is_string($param->var->name))
                        ? $param->var->name
                        : 'param';

                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Domain Service constructors must depend on interfaces only; parameter $%s is typed to concrete class %s in %s.',
                        $paramName,
                        $typeName,
                        $className
                    ))->line($param->getLine())->identifier('domainServiceInterfaceOnly')->build();
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<Name>
     */
    private function collectNamedTypes(Node $type): array
    {
        if ($type instanceof Name) {
            return [$type];
        }

        if ($type instanceof NullableType) {
            /** @var list<Name> $inner */
            $inner = $this->collectNamedTypes($type->type);

            return $inner;
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            $collected = [];
            foreach ($type->types as $inner) {
                /** @var list<Name> $partial */
                $partial   = $this->collectNamedTypes($inner);
                $collected = array_merge($collected, $partial);
            }

            /* @var list<Name> $collected */
            return $collected;
        }

        // Other node kinds (identifiers for built-ins etc.) are ignored
        return [];
    }

    /**
     * @return list<string>
     */
    private function candidateClassNames(string $unqualifiedTypeName, string $declaringNamespace): array
    {
        // If already fully-qualified
        if (str_contains($unqualifiedTypeName, '\\')) {
            return [ltrim($unqualifiedTypeName, '\\')];
        }

        if ($declaringNamespace === '') {
            return [$unqualifiedTypeName];
        }

        return [
            $declaringNamespace . '\\' . $unqualifiedTypeName,
            $unqualifiedTypeName,
        ];
    }
}
