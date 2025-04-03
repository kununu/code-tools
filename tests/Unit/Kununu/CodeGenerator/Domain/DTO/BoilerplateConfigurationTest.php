<?php

declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Domain\DTO;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('code-generator')]
final class BoilerplateConfigurationTest extends TestCase
{
    private BoilerplateConfiguration $config;

    protected function setUp(): void
    {
        $this->config = new BoilerplateConfiguration();
    }

    public function testDefaultValues(): void
    {
        $this->assertNull($this->config->openApiFilePath);
        $this->assertNull($this->config->operationId);
        $this->assertNull($this->config->operationDetails);
        $this->assertSame('src', $this->config->basePath);
        $this->assertSame('App', $this->config->namespace);
        $this->assertSame([], $this->config->templateVariables);
        $this->assertSame([], $this->config->pathPatterns);
        $this->assertSame([], $this->config->generators);
        $this->assertFalse($this->config->force);
        $this->assertFalse($this->config->skipExisting);
        $this->assertSame([], $this->config->existingFiles);
        $this->assertSame([], $this->config->skipFiles);
        $this->assertNull($this->config->templateDir);
    }

    public function testSetOpenApiFilePath(): void
    {
        $path = '/path/to/openapi.yaml';
        $result = $this->config->setOpenApiFilePath($path);

        $this->assertSame($path, $this->config->openApiFilePath);
        $this->assertSame($this->config, $result);
    }

    public function testSetOperationId(): void
    {
        $operationId = 'getUserById';
        $result = $this->config->setOperationId($operationId);

        $this->assertSame($operationId, $this->config->operationId);
        $this->assertSame($this->config, $result);
    }

    public function testSetForce(): void
    {
        $result = $this->config->setForce(true);

        $this->assertTrue($this->config->force);
        $this->assertSame($this->config, $result);
    }

    public function testSetSkipExisting(): void
    {
        $result = $this->config->setSkipExisting(true);

        $this->assertTrue($this->config->skipExisting);
        $this->assertSame($this->config, $result);
    }

    public function testAddSkipFile(): void
    {
        $filePath = 'src/Controller/UserController.php';
        $result = $this->config->addSkipFile($filePath);

        $this->assertContains($filePath, $this->config->skipFiles);
        $this->assertSame($this->config, $result);

        // Add another file
        $anotherFile = 'src/Repository/UserRepository.php';
        $this->config->addSkipFile($anotherFile);

        $this->assertContains($filePath, $this->config->skipFiles);
        $this->assertContains($anotherFile, $this->config->skipFiles);
        $this->assertCount(2, $this->config->skipFiles);
    }

    public function testSetOperationDetails(): void
    {
        $details = [
            'id'          => 'getUserById',
            'summary'     => 'Get user by ID',
            'description' => 'Returns a user by their unique identifier',
            'path'        => '/users/{id}',
            'method'      => 'GET',
            'parameters'  => [['name' => 'id', 'in' => 'path']],
        ];

        $result = $this->config->setOperationDetails($details);

        $this->assertSame($details, $this->config->operationDetails);
        $this->assertSame($this->config, $result);

        // Verify template variables were extracted
        $this->assertSame('getUserById', $this->config->templateVariables['operation_id']);
        $this->assertSame('Get user by ID', $this->config->templateVariables['summary']);
        $this->assertSame('Returns a user by their unique identifier', $this->config->templateVariables['description']);
        $this->assertSame('/users/{id}', $this->config->templateVariables['path']);
        $this->assertSame('GET', $this->config->templateVariables['method']);
        $this->assertSame([['name' => 'id', 'in' => 'path']], $this->config->templateVariables['parameters']);
    }

    public function testSetOperationDetailsWithNull(): void
    {
        $result = $this->config->setOperationDetails(null);

        $this->assertNull($this->config->operationDetails);
        $this->assertSame($this->config, $result);
        $this->assertSame([], $this->config->templateVariables);
    }

    public function testSetBasePath(): void
    {
        $path = 'custom/src';
        $result = $this->config->setBasePath($path);

        $this->assertSame($path, $this->config->basePath);
        $this->assertSame($this->config, $result);
    }

    public function testSetNamespace(): void
    {
        $namespace = 'App\\Custom';
        $result = $this->config->setNamespace($namespace);

        $this->assertSame($namespace, $this->config->namespace);
        $this->assertSame($namespace, $this->config->templateVariables['namespace']);
        $this->assertSame($this->config, $result);
    }

    public function testSetPathPatterns(): void
    {
        $patterns = [
            'controller' => '{basePath}/Controller/{operationName}Controller.php',
            'repository' => '{basePath}/Repository/{entityName}Repository.php',
        ];

        $result = $this->config->setPathPatterns($patterns);

        $this->assertSame($patterns, $this->config->pathPatterns);
        $this->assertSame($this->config, $result);
    }

    public function testSetGenerators(): void
    {
        $generators = [
            'controller' => true,
            'repository' => false,
        ];

        $result = $this->config->setGenerators($generators);

        $this->assertSame($generators, $this->config->generators);
        $this->assertSame($this->config, $result);
    }

    public function testSetTemplateDir(): void
    {
        $dir = '/path/to/templates';
        $result = $this->config->setTemplateDir($dir);

        $this->assertSame($dir, $this->config->templateDir);
        $this->assertSame($this->config, $result);
    }

    public function testAddTemplateVariable(): void
    {
        $name = 'entity_name';
        $value = 'User';
        $result = $this->config->addTemplateVariable($name, $value);

        $this->assertSame($value, $this->config->templateVariables[$name]);
        $this->assertSame($this->config, $result);

        // Add another variable
        $this->config->addTemplateVariable('pluralized_name', 'Users');

        $this->assertSame('User', $this->config->templateVariables['entity_name']);
        $this->assertSame('Users', $this->config->templateVariables['pluralized_name']);
    }

    /**
     * @dataProvider operationDetailsCqrsTypeProvider
     */
    public function testGetTemplateVariablesWithCqrsType(string $method, string $expectedCqrsType): void
    {
        $this->config->setOperationDetails(['method' => $method]);
        $this->config->setBasePath('custom/src');

        $variables = $this->config->getTemplateVariables();

        $this->assertSame('custom/src', $variables['basePath']);
        $this->assertSame($expectedCqrsType, $variables['cqrsType']);
    }

    public static function operationDetailsCqrsTypeProvider(): array
    {
        return [
            'GET method'    => ['GET', 'Query'],
            'POST method'   => ['POST', 'Command'],
            'PUT method'    => ['PUT', 'Command'],
            'PATCH method'  => ['PATCH', 'Command'],
            'DELETE method' => ['DELETE', 'Command'],
        ];
    }
}
