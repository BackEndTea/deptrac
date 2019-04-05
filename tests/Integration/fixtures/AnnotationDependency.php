<?php

declare(strict_types=1);

namespace Tests\SensioLabs\Deptrac\Integration\fixtures;

class AnnotationDependency
{
    /**
     * @var AnnotationDependencyChild
     */
    public $property;

    public function test(): void
    {
        /** @var ?AnnotationDependencyChild $test */
        $test = null;

        /** @var AnnotationDependencyChild[] $children */
        $children = [];
    }
}

class AnnotationDependencyChild
{
}
