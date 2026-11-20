<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Architecture\Rules;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final readonly class CommonRules extends RuleSet
{
    public function testControllersAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(sprintf('/^%s\\\\.*Controller$/', preg_quote(self::CONTROLLER, '/')), true)
            )
            ->excluding(Selector::isAbstract(), Selector::isInterface())
            ->should()
            ->beFinal()
            ->because('A controller is an entrypoint, not a base class. Share behaviour through an abstract class.')
            ->nonIgnorable();
    }

    public function testControllersDoNotDependOnDoctrine(): Rule
    {
        return self::forbidDependency(
            from: [self::CONTROLLER],
            on: [self::DOCTRINE],
            because: 'Controllers reach persistence through a use case or a service, never directly.'
        );
    }

    public function testDomainDoesNotDependOnHttpConsoleOrBundles(): Rule
    {
        return self::forbidDependency(
            from: [self::DOMAIN],
            on: [
                self::SYMFONY_HTTP_FOUNDATION,
                self::SYMFONY_HTTP_KERNEL,
                self::SYMFONY_BUNDLE,
                self::SYMFONY_CONSOLE,
            ],
            because: 'The domain does not know how it is delivered (HTTP or console) or which framework bundles run it.'
        );
    }

    public function testDomainDoesNotDependOnMessaging(): Rule
    {
        return self::forbidDependency(
            from: [self::DOMAIN],
            on: [self::MESSAGING],
            because: 'The domain does not know how its changes are published.'
        );
    }

    public function testLayersDoNotDependOnConsoleCommands(): Rule
    {
        return self::forbidDependency(
            from: [self::CONTROLLER, self::DOMAIN, self::INFRASTRUCTURE, self::MESSAGING],
            on: [self::CONSOLE_COMMAND],
            because: 'A console command is an entrypoint. Move the shared logic out of it instead of calling it.'
        );
    }

    public function testOnlyControllersDependOnControllers(): Rule
    {
        return self::forbidDependency(
            from: [self::APPLICATION],
            on: [self::CONTROLLER],
            because: 'A controller is an entrypoint. Move the shared logic out of it instead of calling it.',
            except: [self::CONTROLLER]
        );
    }

    public function testProductionCodeDoesNotDependOnTests(): Rule
    {
        return self::forbidDependency(
            from: [self::APPLICATION],
            on: [self::TESTS],
            because: 'Test code is not shipped, so production code must not need it.'
        );
    }
}
