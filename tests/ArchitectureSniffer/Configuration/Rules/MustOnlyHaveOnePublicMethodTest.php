<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustOnlyHaveOnePublicMethod;
use PHPat\Rule\Assertion\Declaration\ShouldHaveOnlyOnePublicMethodNamed\ShouldHaveOnlyOnePublicMethodNamed;
use PHPUnit\Framework\TestCase;

final class MustOnlyHaveOnePublicMethodTest extends TestCase
{
    public function testCreateRuleReturnsRule(): void
    {
        $group = new Group(
            name: 'handlers',
            flattenedIncludes: ['App\\Handler\\MyHandler'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: 'handle',
        );
        $library = new ArchitectureLibrary([
            'handlers' => [
                'includes'                               => ['App\\Handler\\MyHandler'],
                'must_only_have_one_public_method_named' => 'handle',
            ],
        ]);

        $result = MustOnlyHaveOnePublicMethod::createRule($group, $library);

        self::assertEquals(ShouldHaveOnlyOnePublicMethodNamed::class, $result()->assertion);
    }
}
