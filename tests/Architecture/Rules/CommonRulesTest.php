<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\Architecture\Rules;

use Kununu\CodeTools\Architecture\Rules\CommonRules;

final class CommonRulesTest extends RuleSetTestCase
{
    protected static function ruleSet(): string
    {
        return CommonRules::class;
    }

    protected static function expectedViolations(): array
    {
        return [
            'testControllersAreFinal' => [
                'src/Controller/IgnoredNotFinalController.php' => [
                    'be final',
                ],
                'src/Controller/NotFinalController.php' => [
                    'be final',
                ],
            ],
            'testControllersDoNotDependOnDoctrine' => [
                'src/Controller/UsesDoctrineController.php' => [
                    'not depend on Doctrine\DBAL\ArchitectureFixture',
                ],
            ],
            'testDomainDoesNotDependOnHttpConsoleOrBundles' => [
                'src/Domain/UsesHttpConsoleAndBundles.php' => [
                    'not depend on Symfony\Bundle\FrameworkBundle\ArchitectureFixture',
                    'not depend on Symfony\Component\Console\ArchitectureFixture',
                    'not depend on Symfony\Component\HttpFoundation\ArchitectureFixture',
                    'not depend on Symfony\Component\HttpKernel\ArchitectureFixture',
                ],
            ],
            'testDomainDoesNotDependOnMessaging' => [
                'src/Domain/UsesMessaging.php' => [
                    'not depend on App\Messaging\CleanProducer',
                ],
            ],
            'testLayersDoNotDependOnConsoleCommands' => [
                'src/Controller/UsesConsoleCommandController.php' => [
                    'not depend on App\Command\CleanCommand',
                ],
                'src/Domain/UsesConsoleCommand.php' => [
                    'not depend on App\Command\CleanCommand',
                ],
                'src/Infrastructure/UsesConsoleCommand.php' => [
                    'not depend on App\Command\CleanCommand',
                ],
                'src/Messaging/UsesConsoleCommand.php' => [
                    'not depend on App\Command\CleanCommand',
                ],
            ],
            'testOnlyControllersDependOnControllers' => [
                'src/Service/UsesController.php' => [
                    'not depend on App\Controller\CleanController',
                ],
            ],
            'testProductionCodeDoesNotDependOnTests' => [
                'src/Service/UsesTests.php' => [
                    'not depend on App\Tests\ArchitectureFixture',
                ],
            ],
        ];
    }
}
