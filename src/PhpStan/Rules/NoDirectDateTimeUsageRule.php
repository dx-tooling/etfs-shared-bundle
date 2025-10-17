<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\New_>
 */
final class NoDirectDateTimeUsageRule implements Rule
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        $className = $node->class->toString();

        // Check for direct DateTime usage
        if (in_array($className, ['DateTime', 'DateTimeImmutable', 'DateTimeInterface'], true)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Direct usage of %s is not allowed. Use DateAndTimeService::getDateTimeImmutable() instead.',
                        $className
                    )
                )->line($node->getLine())->identifier('noDirectDateTimeUsage')->build(),
            ];
        }

        return [];
    }
}
