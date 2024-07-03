<?php

declare(strict_types=1);

namespace Rector\TypeDeclaration\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\Rector\AbstractScopeAwareRector;
use Rector\ValueObject\PhpVersion;
use Rector\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector\StringReturnTypeFromStrictScalarReturnsRectorTest
 */
final class StringReturnTypeFromStrictScalarReturnsRector extends AbstractScopeAwareRector implements MinPhpVersionInterface
{
    public function __construct(
        private readonly ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard,
        private readonly BetterNodeFinder $betterNodeFinder,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add string return type based on returned string scalar values', [
            new CodeSample(
                <<<'CODE_SAMPLE'
final class SomeClass
{
    public function foo($condition)
    {
        if ($condition) {
            return 'yes';
        }

        return 'no';
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
final class SomeClass
{
    public function foo($condition): string
    {
        if ($condition) {
            return 'yes';
        }

        return 'no';
    }
}
CODE_SAMPLE
                ,
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }

    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactorWithScope(Node $node, Scope $scope): ?Node
    {
        // already added → skip
        if ($node->returnType instanceof Node) {
            return null;
        }

        $returns = $this->betterNodeFinder->findReturnsScoped($node);
        if ($returns === []) {
            // void
            return null;
        }

        foreach ($returns as $return) {
            // we need exact string "value" return
            if (! $return->expr instanceof String_ && ! $return->expr instanceof Encapsed) {
                return null;
            }
        }

        if ($this->shouldSkipClassMethodForOverride($node, $scope)) {
            return null;
        }

        $node->returnType = new Identifier('string');
        return $node;
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_70;
    }

    private function shouldSkipClassMethodForOverride(ClassMethod|Function_ $functionLike, Scope $scope): bool
    {
        if (! $functionLike instanceof ClassMethod) {
            return false;
        }

        return $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($functionLike, $scope);
    }
}