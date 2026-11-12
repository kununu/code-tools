<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\Architecture\Rules;

use Kununu\CodeTools\Architecture\Rules\CqrsRules;

final class CqrsRulesTest extends RuleSetTestCase
{
    protected static function ruleSet(): string
    {
        return CqrsRules::class;
    }

    protected static function expectedViolations(): array
    {
        return [
            'testConsoleCommandsDoNotDependOnHttpRequestHandling' => [
                'src/Command/UsesHttpRequestHandlingCommand.php' => [
                    'not depend on App\Request\CleanRequest',
                    'not depend on App\Security\CleanVoter',
                    'not depend on App\Validator\CleanValidator',
                    'not depend on Symfony\Component\HttpFoundation\ArchitectureFixture',
                    'not depend on Symfony\Component\HttpKernel\ArchitectureFixture',
                    'not depend on Symfony\Component\Routing\ArchitectureFixture',
                ],
            ],
            'testControllersDoNotDependOnClientLibrariesOrReadModels' => [
                'src/Controller/UsesClientLibrariesAndReadModelsController.php' => [
                    'not depend on App\Projection\CleanProjection',
                    'not depend on App\Provider\CleanProvider',
                    'not depend on Elastic\Elasticsearch\ArchitectureFixture',
                    'not depend on Elasticsearch\ArchitectureFixture',
                    'not depend on GuzzleHttp\ArchitectureFixture',
                    'not depend on Kununu\Doctrine\ArchitectureFixture',
                    'not depend on Kununu\Elasticsearch\ArchitectureFixture',
                    'not depend on OpenSearch\ArchitectureFixture',
                    'not depend on Symfony\Component\Console\ArchitectureFixture',
                ],
            ],
            'testDomainDoesNotDependOnClientOrFrameworkLibraries' => [
                'src/Domain/UsesClientAndFrameworkLibraries.php' => [
                    'not depend on Aws\ArchitectureFixture',
                    'not depend on Elastic\Elasticsearch\ArchitectureFixture',
                    'not depend on Elasticsearch\ArchitectureFixture',
                    'not depend on GuzzleHttp\ArchitectureFixture',
                    'not depend on Kununu\Doctrine\ArchitectureFixture',
                    'not depend on Kununu\Elasticsearch\ArchitectureFixture',
                    'not depend on Kununu\Hermes\ArchitectureFixture',
                    'not depend on League\Tactician\ArchitectureFixture',
                    'not depend on OpenSearch\ArchitectureFixture',
                    'not depend on Symfony\Component\DependencyInjection\ArchitectureFixture',
                    'not depend on Symfony\Component\EventDispatcher\ArchitectureFixture',
                    'not depend on Symfony\Component\Routing\ArchitectureFixture',
                    'not depend on Symfony\Component\Security\ArchitectureFixture',
                    'not depend on Symfony\Component\Serializer\ArchitectureFixture',
                ],
            ],
            'testDomainDoesNotDependOnInputOrReadModelLayers' => [
                'src/Domain/UsesInputAndReadModelLayers.php' => [
                    'not depend on App\DataFixtures\CleanFixture',
                    'not depend on App\Projection\CleanProjection',
                    'not depend on App\Provider\CleanProvider',
                    'not depend on App\Request\CleanRequest',
                    'not depend on App\Security\CleanVoter',
                    'not depend on App\Validator\CleanValidator',
                ],
            ],
            'testInfrastructureDoesNotDependOnEntrypointOrReadModelLayers' => [
                'src/Infrastructure/UsesEntrypointAndReadModelLayers.php' => [
                    'not depend on App\Projection\CleanProjection',
                    'not depend on App\Provider\CleanProvider',
                    'not depend on App\Request\CleanRequest',
                    'not depend on App\Security\CleanVoter',
                    'not depend on App\Validator\CleanValidator',
                    'not depend on Symfony\Component\Console\ArchitectureFixture',
                    'not depend on Symfony\Component\HttpKernel\ArchitectureFixture',
                    'not depend on Symfony\Component\Routing\ArchitectureFixture',
                ],
            ],
            'testMessagingDoesNotDependOnOrmSearchOrHttp' => [
                'src/Messaging/UsesOrmSearchAndHttp.php' => [
                    'not depend on App\Request\CleanRequest',
                    'not depend on App\Validator\CleanValidator',
                    'not depend on Doctrine\ORM\ArchitectureFixture',
                    'not depend on Elastic\Elasticsearch\ArchitectureFixture',
                    'not depend on Elasticsearch\ArchitectureFixture',
                    'not depend on GuzzleHttp\ArchitectureFixture',
                    'not depend on Kununu\Doctrine\ArchitectureFixture',
                    'not depend on Kununu\Elasticsearch\ArchitectureFixture',
                    'not depend on OpenSearch\ArchitectureFixture',
                    'not depend on Symfony\Component\HttpFoundation\ArchitectureFixture',
                    'not depend on Symfony\Component\HttpKernel\ArchitectureFixture',
                ],
            ],
            'testQueriesDoNotDependOnCommands' => [
                'src/UseCase/Query/UsesCommand.php' => [
                    'not depend on App\UseCase\Command\CleanCommandHandler',
                ],
            ],
            'testRequestsDoNotDependOnInfrastructureOrBuses' => [
                'src/Request/UsesInfrastructureAndBuses.php' => [
                    'not depend on App\Command\CleanCommand',
                    'not depend on App\Infrastructure\CleanRepository',
                    'not depend on App\Messaging\CleanProducer',
                    'not depend on Doctrine\DBAL\ArchitectureFixture',
                    'not depend on Kununu\CQRS\ArchitectureFixture',
                ],
            ],
            'testUseCasesDoNotDependOnEntrypointsFixturesOrSerializer' => [
                'src/UseCase/Command/UsesEntrypointsFixturesAndSerializer.php' => [
                    'not depend on App\Command\CleanCommand',
                    'not depend on App\DataFixtures\CleanFixture',
                    'not depend on App\Security\CleanVoter',
                    'not depend on Symfony\Component\Console\ArchitectureFixture',
                    'not depend on Symfony\Component\Routing\ArchitectureFixture',
                    'not depend on Symfony\Component\Serializer\ArchitectureFixture',
                ],
            ],
        ];
    }
}
