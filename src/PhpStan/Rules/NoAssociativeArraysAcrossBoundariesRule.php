<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * For public/protected methods and interface methods, disallow associative/complex arrays
 * in parameter types and return types. Only simple lists are allowed: list<T> where T is not an array and not mixed.
 * Private methods are ignored to allow local usage within a class.
 *
 * @implements Rule<ClassMethod>
 */
final class NoAssociativeArraysAcrossBoundariesRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $isInterface = $classReflection->isInterface();
        $isPrivate   = $node->isPrivate();
        $isPublic    = $node->isPublic();
        $isProtected = $node->isProtected();

        // Ignore private methods entirely; they are considered local to the class
        if (!$isInterface && $isPrivate) {
            return [];
        }

        // Only enforce for public/protected methods and all interface methods (implicitly public)
        if (!$isInterface && !$isPublic && !$isProtected) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!$classReflection->hasMethod($methodName)) {
            return [];
        }
        $methodReflection = $classReflection->getMethod($methodName, $scope);

        $errors              = [];
        $parametersAcceptors = $methodReflection->getVariants();
        foreach ($parametersAcceptors as $variant) {
            $errors = array_merge(
                $errors,
                $this->checkParameters($variant, $classReflection->getName(), $methodName, $node)
            );
            $errors = array_merge($errors, $this->checkReturnType($variant, $classReflection->getName(), $methodName, $node));
        }

        return $errors;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function checkParameters(ParametersAcceptor $variant, string $className, string $methodName, ClassMethod $node): array
    {
        $errors = [];
        foreach ($variant->getParameters() as $parameterReflection) {
            $paramType = $parameterReflection->getType();
            if ($this->containsDisallowedArray($paramType)) {
                $paramName = $parameterReflection->getName();
                $errors[]  = RuleErrorBuilder::message(sprintf(
                    'Only simple lists (list<T>, non-array T, non-mixed T) are allowed across class boundaries; parameter $%s of %s::%s() uses an associative/complex array type. Define a dedicated DTO instead.',
                    $paramName,
                    $className,
                    $methodName
                ))->line($node->getLine())->identifier('noAssociativeArraysAcrossBoundaries.param')->build();
            }
        }

        return $errors;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function checkReturnType(ParametersAcceptor $variant, string $className, string $methodName, ClassMethod $node): array
    {
        $errors = [];

        // Heuristic: inspect raw PHPDoc for array-based returns (array<...>, array{...}, list<array...>)
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            $doc                = $docComment->getText();
            $hasArrayLikeReturn = preg_match('/@return\s+.*(list\s*<\s*array|array\s*<|array\s*\{)/i', $doc) === 1;
            if ($hasArrayLikeReturn) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Only simple lists (list<T>, non-array T, non-mixed T) are allowed across class boundaries; return type of %s::%s() uses an associative/complex array type. Define a dedicated DTO instead.',
                    $className,
                    $methodName
                ))->line($node->getLine())->identifier('noAssociativeArraysAcrossBoundaries.return')->build();

                return $errors;
            }
        }

        $returnType = $variant->getReturnType();
        if ($this->containsDisallowedArray($returnType)) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Only simple lists (list<T>, non-array T, non-mixed T) are allowed across class boundaries; return type of %s::%s() uses an associative/complex array type. Define a dedicated DTO instead.',
                $className,
                $methodName
            ))->line($node->getLine())->identifier('noAssociativeArraysAcrossBoundaries.return')->build();
        }

        return $errors;
    }

    private function containsDisallowedArray(Type $type): bool
    {
        // Unions: if any variant contains a disallowed array, the whole type is disallowed
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $inner) {
                if ($this->containsDisallowedArray($inner)) {
                    return true;
                }
            }

            return false;
        }

        // Array shapes are always considered complex/disallowed across boundaries
        if (count($type->getConstantArrays()) > 0) {
            return true;
        }

        // Generic arrays: allowed only if keys are integers (list-like) AND item type is not array-like AND not mixed
        foreach ($type->getArrays() as $arrayType) {
            $keyType   = $arrayType->getKeyType();
            $valueType = $arrayType->getItemType();

            $isIntegerKeys = (new IntegerType())->isSuperTypeOf($keyType)->yes();
            if (!$isIntegerKeys) {
                return true; // associative or unknown keys
            }

            if ($this->isArrayLike($valueType)) {
                return true; // nested arrays are disallowed
            }

            if ($valueType instanceof MixedType) {
                return true; // mixed types are disallowed
            }
        }

        return false;
    }

    private function isArrayLike(Type $type): bool
    {
        if ($type->isArray()->yes()) {
            return true;
        }

        if (count($type->getArrays()) > 0) {
            return true;
        }

        if (count($type->getConstantArrays()) > 0) {
            return true;
        }

        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $inner) {
                if ($this->isArrayLike($inner)) {
                    return true;
                }
            }
        }

        return false;
    }
}
