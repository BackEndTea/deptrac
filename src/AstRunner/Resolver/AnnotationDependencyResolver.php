<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac\AstRunner\Resolver;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use SensioLabs\Deptrac\AstRunner\AstMap\AstClassReference;
use SensioLabs\Deptrac\AstRunner\AstMap\AstDependency;

class AnnotationDependencyResolver implements ClassDependencyResolver
{
    private $lexer;
    private $docParser;

    public function __construct()
    {
        $this->lexer = new Lexer();
        $this->docParser = new PhpDocParser(new TypeParser(), new ConstExprParser());
    }

    public function processNode(Node $node, AstClassReference $astClassReference): void
    {
        if (!$node instanceof Node\Stmt\Property && !$node instanceof Node\Expr\Variable) {
            return;
        }

        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            return;
        }

        $typeResolver = new TypeResolver(new NameScope($astClassReference));
        $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $docNode = $this->docParser->parse($tokens);

        foreach ($docNode->getVarTagValues() as $tag) {
            $types = $typeResolver->resolveType($tag->type);

            foreach ($types as $type) {
                $astClassReference->addDependency(AstDependency::variable($type, $docComment->getLine()));
            }
        }
    }
}
