<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Helper;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Helper\RuleBuilder;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPUnit\Framework\TestCase;

final class RuleBuilderTest extends TestCase
{
    public function testGetRulesYieldsNoRulesForMinimalGroup(): void
    {
        $group = new Group(
            name: 'minimal',
            flattenedIncludes: ['App\\Service\\'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(0, $rules);
    }

    public function testGetRulesYieldsFinalRule(): void
    {
        $group = new Group(
            name: 'finalOnly',
            flattenedIncludes: ['App\\Service\\'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: true,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(1, $rules);
        self::assertInstanceOf(PHPatRule::class, $rules[0]);
    }

    public function testGetRulesYieldsReadonlyRule(): void
    {
        $group = new Group(
            name: 'readonlyOnly',
            flattenedIncludes: ['App\\Service\\'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: true,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(1, $rules);
    }

    public function testGetRulesYieldsExtendRule(): void
    {
        $group = new Group(
            name: 'extendsOnly',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: 'App\\Base\\AbstractService',
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(1, $rules);
    }

    public function testGetRulesYieldsImplementRule(): void
    {
        $group = new Group(
            name: 'implementsOnly',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: ['App\\Contract\\ServiceInterface'],
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(1, $rules);
    }

    public function testGetRulesYieldsDependOnRule(): void
    {
        $group = new Group(
            name: 'dependsOnly',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: ['App\\Repository\\'],
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(1, $rules);
    }

    public function testGetRulesYieldsMustNotDependOnRule(): void
    {
        $group = new Group(
            name: 'notDependOnly',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: ['App\\Controller\\'],
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(1, $rules);
    }

    public function testGetRulesYieldsPublicMethodRules(): void
    {
        $group = new Group(
            name: 'publicMethodOnly',
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
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(2, $rules);
    }

    public function testGetRulesYieldsAllRulesForFullGroup(): void
    {
        $group = new Group(
            name: 'fullGroup',
            flattenedIncludes: ['App\\Handler\\MyHandler'],
            flattenedExcludes: null,
            dependsOn: ['App\\Service\\'],
            mustNotDependOn: ['App\\Controller\\'],
            extends: 'App\\Base\\AbstractHandler',
            implements: ['App\\Contract\\HandlerInterface'],
            isFinal: true,
            isReadonly: true,
            mustOnlyHaveOnePublicMethodName: 'execute',
        );
        $library = $this->createLibraryForGroup($group);

        $rules = iterator_to_array(RuleBuilder::getRules($group, $library));

        self::assertCount(8, $rules);
    }

    private function createLibraryForGroup(Group $group): ArchitectureLibrary
    {
        $attributes = [
            'includes' => $group->flattenedIncludes,
        ];

        if ($group->dependsOn !== null) {
            $attributes['depends_on'] = $group->dependsOn;
        }
        if ($group->mustNotDependOn !== null) {
            $attributes['must_not_depend_on'] = $group->mustNotDependOn;
        }
        if ($group->extends !== null) {
            $attributes['extends'] = $group->extends;
        }
        if ($group->implements !== null) {
            $attributes['implements'] = $group->implements;
        }
        if ($group->isFinal) {
            $attributes['final'] = true;
        }
        if ($group->isReadonly) {
            $attributes['readonly'] = true;
        }
        if ($group->mustOnlyHaveOnePublicMethodName !== null) {
            $attributes['must_only_have_one_public_method_named'] = $group->mustOnlyHaveOnePublicMethodName;
        }

        return new ArchitectureLibrary([
            $group->name => $attributes,
        ]);
    }
}
