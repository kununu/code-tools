<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Infrastructure\Template\StringTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StringTransformerTest extends TestCase
{
    private StringTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StringTransformer();
    }

    #[DataProvider('operationIdToClassNameProvider')]
    public function testOperationIdToClassName(string $operationId, string $expected): void
    {
        $result = $this->transformer->operationIdToClassName($operationId);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('extractEntityNameProvider')]
    public function testExtractEntityNameFromOperationId(string $operationId, string $expected): void
    {
        $result = $this->transformer->extractEntityNameFromOperationId($operationId);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('snakeToCamelCaseProvider')]
    public function testSnakeToCamelCase(string $input, string $expected): void
    {
        $result = $this->transformer->snakeToCamelCase($input);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('generateOutputPathProvider')]
    public function testGenerateOutputPath(string $pattern, string $basePath, array $variables, string $expected): void
    {
        $result = $this->transformer->generateOutputPath($pattern, $basePath, $variables);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('namespaceProvider')]
    public function testGetDynamicNamespace(
        string $outputPath,
        string $basePath,
        string $baseNamespace,
        string $expected,
    ): void {
        $result = $this->transformer->getDynamicNamespace($outputPath, $basePath, $baseNamespace);
        $this->assertSame($expected, $result);
    }

    public static function operationIdToClassNameProvider(): array
    {
        return [
            'empty string'       => ['', ''],
            'simple lowercase'   => ['user', 'User'],
            'simple camelcase'   => ['getUser', 'GetUser'],
            'complex camelcase'  => ['getUserProfile', 'GetUserProfile'],
            'preserve uppercase' => ['getToneOfVoiceAPI', 'GetToneOfVoiceAPI'],
        ];
    }

    public static function extractEntityNameProvider(): array
    {
        return [
            'empty string'      => ['', ''],
            'simple name'       => ['user', 'User'],
            'get prefix'        => ['getUser', 'User'],
            'create prefix'     => ['createUserProfile', 'User'],
            'update prefix'     => ['updateUser', 'User'],
            'list suffix'       => ['getUserList', 'User'],
            'collection suffix' => ['getUserCollection', 'User'],
            'complex name'      => ['getUserProfileSettings', 'User'],
        ];
    }

    public static function snakeToCamelCaseProvider(): array
    {
        return [
            'empty string'            => ['', ''],
            'simple word'             => ['user', 'user'],
            'two words'               => ['user_profile', 'userProfile'],
            'three words'             => ['user_profile_settings', 'userProfileSettings'],
            'with leading underscore' => ['_user_profile', 'userProfile'],
        ];
    }

    public static function generateOutputPathProvider(): array
    {
        return [
            'simple path' => [
                '{basePath}/Controller/{operationName}Controller.php',
                'src',
                ['operation_id' => 'getUser'],
                'src/Controller/GetUserController.php',
            ],
            'with entity name' => [
                '{basePath}/UseCase/Query/{operationName}/ReadModel/{entityName}.php',
                'src',
                ['operation_id' => 'getUserProfile', 'entity_name' => 'User'],
                'src/UseCase/Query/GetUserProfile/ReadModel/User.php',
            ],
            'with method' => [
                '{basePath}/Controller/{method}/{operationName}Controller.php',
                'src',
                ['operation_id' => 'getUser', 'method' => 'GET'],
                'src/Controller/get/GetUserController.php',
            ],
            'with cqrs type' => [
                '{basePath}/UseCase/{cqrsType}/{operationName}/Handler.php',
                'src',
                ['operation_id' => 'getUser', 'cqrsType' => 'Query'],
                'src/UseCase/Query/GetUser/Handler.php',
            ],
        ];
    }

    public static function namespaceProvider(): array
    {
        return [
            'simple controller' => [
                'src/Controller/UserController.php',
                'src',
                'App',
                'App\\Controller',
            ],
            'nested controller' => [
                'src/Controller/Admin/UserController.php',
                'src',
                'App',
                'App\\Controller\\Admin',
            ],
            'test file' => [
                'tests/Unit/Controller/UserControllerTest.php',
                'src',
                'App',
                'App\\Tests\\Unit\\Controller',
            ],
            'root file' => [
                'src/UserService.php',
                'src',
                'App',
                'App',
            ],
        ];
    }
}
