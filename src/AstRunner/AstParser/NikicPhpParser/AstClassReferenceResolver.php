<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac\AstRunner\AstParser\NikicPhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use SensioLabs\Deptrac\AstRunner\AstMap\AstClassReference;
use SensioLabs\Deptrac\AstRunner\AstMap\AstDependency;
use SensioLabs\Deptrac\AstRunner\AstMap\AstFileReference;
use SensioLabs\Deptrac\AstRunner\AstMap\AstInherit;

class AstClassReferenceResolver extends NodeVisitorAbstract
{
    private $fileReference;

    /** @var AstClassReference */
    private $currentClassReference;

    public function __construct(AstFileReference $fileReference)
    {
        $this->fileReference = $fileReference;
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof Node\Stmt\ClassLike) {
            return null;
        }

        if (isset($node->namespacedName) && $node->namespacedName instanceof Node\Name) {
            $className = $node->namespacedName->toString();
        } elseif ($node->name instanceof Node\Identifier) {
            $className = $node->name->toString();
        } else {
            return null; // map anonymous classes on current class
        }

        $this->currentClassReference = $this->fileReference->addClassReference($className);

        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends instanceof Node\Name) {
                $this->currentClassReference->addInherit(
                    AstInherit::newExtends($node->extends->toString(), $node->extends->getLine())
                );
            }
            foreach ($node->implements as $implement) {
                $this->currentClassReference->addInherit(
                    AstInherit::newImplements($implement->toString(), $implement->getLine())
                );
            }
        }

        if ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $extend) {
                $this->currentClassReference->addInherit(
                    AstInherit::newExtends($extend->toString(), $extend->getLine())
                );
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\UseUse) {
            $this->fileReference->addDependency(
                AstDependency::useStmt($node->name->toString(), $node->name->getLine())
            );
        }

        if (null === $this->currentClassReference) {
            return null;
        }

        if ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                $this->currentClassReference->addInherit(
                    AstInherit::newTraitUse($trait->toString(), $trait->getLine())
                );
            }
        }

        if ($node instanceof Node\Expr\Instanceof_ && $node->class instanceof Node\Name) {
            $this->currentClassReference->addDependency(
                AstDependency::instanceof($node->class->toString(), $node->class->getLine())
            );
        }

        if ($node instanceof Node\Param && $node->type instanceof Node\Name) {
            $this->currentClassReference->addDependency(
                AstDependency::parameter($node->type->toString(), $node->type->getLine())
            );
        }

        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
            $this->currentClassReference->addDependency(
                AstDependency::newStmt($node->class->toString(), $node->class->getLine())
            );
        }

        if ($node instanceof Node\Expr\StaticPropertyFetch && $node->class instanceof Node\Name) {
            $this->currentClassReference->addDependency(
                AstDependency::staticProperty($node->class->toString(), $node->class->getLine())
            );
        }

        if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name) {
            $this->currentClassReference->addDependency(
                AstDependency::staticMethod($node->class->toString(), $node->class->getLine())
            );
        }

        if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Expr\Closure) {
            if ($node->returnType instanceof Node\Name) {
                $this->currentClassReference->addDependency(
                    AstDependency::returnType($node->returnType->toString(), $node->returnType->getLine())
                );
            } elseif ($node->returnType instanceof Node\NullableType) {
                $this->currentClassReference->addDependency(
                    AstDependency::returnType((string) $node->returnType->type, $node->returnType->getLine())
                );
            }
        }

        if ($node instanceof Node\Stmt\Catch_) {
            foreach ($node->types as $type) {
                $this->currentClassReference->addDependency(
                    AstDependency::catchStmt($type->toString(), $type->getLine())
                );
            }
        }

        return null;
    }
}
