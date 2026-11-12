<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Architecture\Rules;

use PHPat\Test\Builder\Rule;

final readonly class CqrsRules extends RuleSet
{
    private const string AWS = 'Aws';
    private const string DATA_FIXTURES = 'App\DataFixtures';
    private const string DOCTRINE_ORM = 'Doctrine\ORM';
    private const string GUZZLE = 'GuzzleHttp';
    private const string KUNUNU_CQRS = 'Kununu\CQRS';
    private const string KUNUNU_DOCTRINE = 'Kununu\Doctrine';
    private const string KUNUNU_HERMES = 'Kununu\Hermes';
    private const string PROJECTION = 'App\Projection';
    private const string PROVIDER = 'App\Provider';
    private const string REQUEST = 'App\Request';
    private const string SECURITY = 'App\Security';
    private const string SYMFONY_DEPENDENCY_INJECTION = 'Symfony\Component\DependencyInjection';
    private const string SYMFONY_EVENT_DISPATCHER = 'Symfony\Component\EventDispatcher';
    private const string SYMFONY_ROUTING = 'Symfony\Component\Routing';
    private const string SYMFONY_SECURITY = 'Symfony\Component\Security';
    private const string SYMFONY_SERIALIZER = 'Symfony\Component\Serializer';
    private const string TACTICIAN = 'League\Tactician';
    private const string USE_CASE = 'App\UseCase';
    private const string USE_CASE_COMMAND = 'App\UseCase\Command';
    private const string USE_CASE_QUERY = 'App\UseCase\Query';
    private const string VALIDATOR = 'App\Validator';
    private const array SEARCH_ENGINES = [
        'Elasticsearch',
        'Elastic\Elasticsearch',
        'OpenSearch',
        'Kununu\Elasticsearch',
    ];

    public function testConsoleCommandsDoNotDependOnHttpRequestHandling(): Rule
    {
        return self::forbidDependency(
            from: [self::CONSOLE_COMMAND],
            on: [
                self::SYMFONY_HTTP_FOUNDATION,
                self::SYMFONY_HTTP_KERNEL,
                self::SYMFONY_ROUTING,
                self::REQUEST,
                self::VALIDATOR,
                self::SECURITY,
            ],
            because: 'A console command is not an HTTP entrypoint: no HTTP classes, requests, validators or voters.'
        );
    }

    public function testControllersDoNotDependOnClientLibrariesOrReadModels(): Rule
    {
        return self::forbidDependency(
            from: [self::CONTROLLER],
            on: [
                self::KUNUNU_DOCTRINE,
                ...self::SEARCH_ENGINES,
                self::GUZZLE,
                self::SYMFONY_CONSOLE,
                self::PROJECTION,
                self::PROVIDER,
            ],
            because: 'Controllers dispatch commands and queries. They do not call clients, projections or providers.'
        );
    }

    public function testDomainDoesNotDependOnClientOrFrameworkLibraries(): Rule
    {
        return self::forbidDependency(
            from: [self::DOMAIN],
            on: [
                self::AWS,
                self::GUZZLE,
                ...self::SEARCH_ENGINES,
                self::KUNUNU_DOCTRINE,
                self::KUNUNU_HERMES,
                self::TACTICIAN,
                self::SYMFONY_ROUTING,
                self::SYMFONY_SECURITY,
                self::SYMFONY_SERIALIZER,
                self::SYMFONY_DEPENDENCY_INJECTION,
                self::SYMFONY_EVENT_DISPATCHER,
            ],
            because: 'The domain is free of infrastructure and framework libraries.'
        );
    }

    public function testDomainDoesNotDependOnInputOrReadModelLayers(): Rule
    {
        return self::forbidDependency(
            from: [self::DOMAIN],
            on: [
                self::REQUEST,
                self::VALIDATOR,
                self::SECURITY,
                self::PROJECTION,
                self::PROVIDER,
                self::DATA_FIXTURES,
            ],
            because: 'The domain does not know how input arrives or is checked, nor the read models built from it.'
        );
    }

    public function testInfrastructureDoesNotDependOnEntrypointOrReadModelLayers(): Rule
    {
        return self::forbidDependency(
            from: [self::INFRASTRUCTURE],
            on: [
                self::REQUEST,
                self::VALIDATOR,
                self::SECURITY,
                self::PROVIDER,
                self::PROJECTION,
                self::SYMFONY_CONSOLE,
                self::SYMFONY_HTTP_KERNEL,
                self::SYMFONY_ROUTING,
            ],
            because: 'Infrastructure implements what use cases need and does not know how a request arrived.'
        );
    }

    public function testMessagingDoesNotDependOnOrmSearchOrHttp(): Rule
    {
        return self::forbidDependency(
            from: [self::MESSAGING],
            on: [
                self::DOCTRINE_ORM,
                self::KUNUNU_DOCTRINE,
                ...self::SEARCH_ENGINES,
                self::GUZZLE,
                self::SYMFONY_HTTP_FOUNDATION,
                self::SYMFONY_HTTP_KERNEL,
                self::REQUEST,
                self::VALIDATOR,
            ],
            because: 'Message handlers dispatch commands and queries, without the ORM, search, HTTP or requests.'
        );
    }

    public function testQueriesDoNotDependOnCommands(): Rule
    {
        return self::forbidDependency(
            from: [self::USE_CASE_QUERY],
            on: [self::USE_CASE_COMMAND],
            because: 'A query only reads. Depending on a command couples the read side to the write side.'
        );
    }

    public function testRequestsDoNotDependOnInfrastructureOrBuses(): Rule
    {
        return self::forbidDependency(
            from: [self::REQUEST],
            on: [
                self::INFRASTRUCTURE,
                self::MESSAGING,
                self::CONSOLE_COMMAND,
                self::DOCTRINE,
                self::KUNUNU_CQRS,
            ],
            because: 'A request object only describes and validates input. It does not load data or dispatch anything.'
        );
    }

    public function testUseCasesDoNotDependOnEntrypointsFixturesOrSerializer(): Rule
    {
        return self::forbidDependency(
            from: [self::USE_CASE],
            on: [
                self::CONSOLE_COMMAND,
                self::DATA_FIXTURES,
                self::SECURITY,
                self::SYMFONY_CONSOLE,
                self::SYMFONY_ROUTING,
                self::SYMFONY_SERIALIZER,
            ],
            because: 'A use case does not know how it was triggered (HTTP, console or message).'
        );
    }
}
