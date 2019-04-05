<?php

declare(strict_types=1);

namespace Tests\SensioLabs\Deptrac\Integration;

use PHPUnit\Framework\TestCase;
use SensioLabs\Deptrac\AstRunner\AstParser\AstFileReferenceInMemoryCache;
use SensioLabs\Deptrac\AstRunner\AstParser\NikicPhpParser\FileParser;
use SensioLabs\Deptrac\AstRunner\AstParser\NikicPhpParser\NikicPhpParser;
use SensioLabs\Deptrac\AstRunner\AstParser\NikicPhpParser\ParserFactory;
use SplFileInfo;

class AnnotationDependencyResolverTest extends TestCase
{
    public function testPropertyDependencyResolving(): void
    {
        $parser = new NikicPhpParser(
            new FileParser(ParserFactory::createParser()), new AstFileReferenceInMemoryCache()
        );

        $astFileReference = $parser->parse(new SplFileInfo(__DIR__.'/fixtures/AnnotationDependency.php'));

        $astClassReferences = $astFileReference->getAstClassReferences();
        $annotationDependency = $astClassReferences[0]->getDependencies();

        static::assertCount(2, $astClassReferences);
        static::assertCount(3, $annotationDependency);
        static::assertCount(0, $astClassReferences[1]->getDependencies());

        static::assertSame(
            'Tests\SensioLabs\Deptrac\Integration\fixtures\AnnotationDependencyChild',
            $annotationDependency[0]->getClass()
        );
        static::assertSame(9, $annotationDependency[0]->getLine());
        static::assertSame('variable', $annotationDependency[0]->getType());

        static::assertSame(
            'Tests\SensioLabs\Deptrac\Integration\fixtures\AnnotationDependencyChild',
            $annotationDependency[1]->getClass()
        );
        static::assertSame(16, $annotationDependency[1]->getLine());
        static::assertSame('variable', $annotationDependency[1]->getType());

        static::assertSame(
            'Tests\SensioLabs\Deptrac\Integration\fixtures\AnnotationDependencyChild',
            $annotationDependency[2]->getClass()
        );
        static::assertSame(19, $annotationDependency[2]->getLine());
        static::assertSame('variable', $annotationDependency[2]->getType());
    }
}
