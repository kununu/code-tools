<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Infrastructure\Template\StringTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringTransformerTest extends TestCase
{
    private StringTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StringTransformer();
    }

    #[DataProvider('operationIdToClassNameDataProvider')]
    public function testOperationIdToClassName(string $operationId, string $expectedClassName): void
    {
        $result = $this->transformer->operationIdToClassName($operationId);

        $this->assertEquals($expectedClassName, $result);
    }

    public static function operationIdToClassNameDataProvider(): array
    {
        return [
            'Simple camelCase'    => ['getUserProfile', 'GetUserProfile'],
            'Multiple words'      => ['getToneOfVoiceSettings', 'GetToneOfVoiceSettings'],
            'Single word'         => ['user', 'User'],
            'Empty string'        => ['', ''],
            'Already capitalized' => ['GetUserProfile', 'GetUserProfile'],
            'With numbers'        => ['getUser123Profile', 'GetUser123Profile'],
            'With special chars'  => ['get_user-profile', 'Get_user-profile'],
            'Single letter'       => ['a', 'A'],
        ];
    }

    #[DataProvider('extractEntityNameFromOperationIdDataProvider')]
    public function testExtractEntityNameFromOperationId(string $operationId, string $expectedEntityName): void
    {
        $result = $this->transformer->extractEntityNameFromOperationId($operationId);

        $this->assertEquals($expectedEntityName, $result);
    }

    public static function extractEntityNameFromOperationIdDataProvider(): array
    {
        return [
            'Get operation'            => ['getUserProfile', 'User'],
            'Create operation'         => ['createUserProfile', 'User'],
            'Update operation'         => ['updateUserProfile', 'User'],
            'Delete operation'         => ['deleteUserProfile', 'User'],
            'Find operation'           => ['findUserProfile', 'User'],
            'List operation'           => ['listUserProfiles', 'User'],
            'With List suffix'         => ['getUserProfileList', 'User'],
            'With Collection suffix'   => ['getUserProfileCollection', 'User'],
            'With Item suffix'         => ['getUserProfileItem', 'User'],
            'With By suffix'           => ['getUserProfileByEmail', 'User'],
            'Empty string'             => ['', ''],
            'No prefix or suffix'      => ['userProfile', 'User'],
            'Complex name'             => ['getToneOfVoiceSettings', 'Tone'],
            'With numbers'             => ['get123User456', '123User456'],
            'Initial caps'             => ['GetUserProfile', 'Get'],
            'Only operation no entity' => ['get', ''],
        ];
    }

    #[DataProvider('snakeToCamelCaseDataProvider')]
    public function testSnakeToCamelCase(string $input, string $expected): void
    {
        $result = $this->transformer->snakeToCamelCase($input);

        $this->assertEquals($expected, $result);
    }

    public static function snakeToCamelCaseDataProvider(): array
    {
        return [
            'Simple snake_case'       => ['user_profile', 'userProfile'],
            'Multiple words'          => ['tone_of_voice_settings', 'toneOfVoiceSettings'],
            'Single word'             => ['user', 'user'],
            'Empty string'            => ['', ''],
            'With leading underscore' => ['_user_profile', 'userProfile'],
            'With numbers'            => ['user_123_profile', 'user123Profile'],
            'Already camelCase'       => ['userProfile', 'userProfile'],
            'Multiple underscores'    => ['user___profile', 'userProfile'],
            'Trailing underscore'     => ['user_profile_', 'userProfile'],
        ];
    }

    #[DataProvider('generateOutputPathDataProvider')]
    public function testGenerateOutputPath(string $pattern, string $basePath, array $variables, string $expected): void
    {
        $result = $this->transformer->generateOutputPath($pattern, $basePath, $variables);

        $this->assertEquals($expected, $result);
    }

    public static function generateOutputPathDataProvider(): array
    {
        return [
            'Basic path with operation_id' => [
                '{basePath}/Controller/{operationName}Controller.php',
                'src',
                ['operation_id' => 'getUserProfile'],
                'src/Controller/GetUserProfileController.php',
            ],
            'Path with entity_name' => [
                '{basePath}/Entity/{entityName}.php',
                'src',
                ['entity_name' => 'user'],
                'src/Entity/User.php',
            ],
            'Path with method' => [
                '{basePath}/Controller/{method}Controller.php',
                'src',
                ['method' => 'GET'],
                'src/Controller/getController.php',
            ],
            'Path with CQRS type' => [
                '{basePath}/Query/{cqrsType}Query.php',
                'src',
                ['cqrsType' => 'UserProfile'],
                'src/Query/UserProfileQuery.php',
            ],
            'Complex path with multiple variables' => [
                '{basePath}/{method}/{entityName}/{operationName}Controller.php',
                'src',
                [
                    'method'       => 'GET',
                    'entity_name'  => 'user',
                    'operation_id' => 'getUserProfile',
                ],
                'src/get/User/GetUserProfileController.php',
            ],
            'Empty pattern' => [
                '',
                'src',
                ['operation_id' => 'getUser'],
                '',
            ],
            'Missing variables' => [
                '{basePath}/Controller/{operationName}Controller.php',
                'src',
                [],
                'src/Controller/{operationName}Controller.php',
            ],
        ];
    }

    public function testGenerateOutputPathWithMethodAsArray(): void
    {
        $pattern = '{basePath}/{method}/Controller.php';
        $basePath = 'src';
        $variables = ['method' => 'GET'];

        $result = $this->transformer->generateOutputPath($pattern, $basePath, $variables);

        $this->assertEquals('src/get/Controller.php', $result);
    }

    #[DataProvider('getDynamicNamespaceDataProvider')]
    public function testGetDynamicNamespace(
        string $outputPath,
        string $basePath,
        string $baseNamespace,
        string $expected,
    ): void {
        $result = $this->transformer->getDynamicNamespace($outputPath, $basePath, $baseNamespace);

        $this->assertEquals($expected, $result);
    }

    public static function getDynamicNamespaceDataProvider(): array
    {
        return [
            'Simple namespace' => [
                'src/Controller/UserController.php',
                'src',
                'App',
                'App\\Controller',
            ],
            'Nested namespace' => [
                'src/Service/User/ProfileService.php',
                'src',
                'App',
                'App\\Service\\User',
            ],
            'Root directory' => [
                'src/User.php',
                'src',
                'App',
                'App',
            ],
            'Test file namespace' => [
                'tests/Unit/Controller/UserControllerTest.php',
                'src',
                'App',
                'App\\Tests\\Unit\\Controller',
            ],
            'Windows path' => [
                'src\\Controller\\UserController.php',
                'src',
                'App',
                'App\\Controller',
            ],
            'Redundant namespace segments' => [
                'src/App/Controller/UserController.php',
                'src',
                'App',
                'App\\Controller',
            ],
            'Empty base path' => [
                'Controller/UserController.php',
                '',
                'App',
                'App\\Controller',
            ],
            'Empty output path' => [
                '',
                'src',
                'App',
                'App',
            ],
            'Test namespace prefix' => [
                'tests/Unit/App/Controller/UserControllerTest.php',
                'src',
                'App',
                'App\\Tests\\Unit\\App\\Controller',
            ],
            'Special src directory' => [
                'src/src/Controller/UserController.php',
                'src',
                'App',
                'App\\src\\Controller',
            ],
            'Directories with dots' => [
                'src/./././Controller/UserController.php',
                'src',
                'App',
                'App\\Controller',
            ],
            'Relative paths' => [
                'src/../src/Controller/UserController.php',
                'src',
                'App',
                'App\\..\src\\Controller',
            ],
            'Alternative test directory' => [
                'test/Unit/Controller/UserControllerTest.php',
                'src',
                'App',
                'App\\test\\Unit\\Controller',
            ],
            'Duplicate Tests in path' => [
                'tests/Tests/Unit/Controller/UserControllerTest.php',
                'src',
                'App',
                'App\\Tests\\Unit\\Controller',
            ],
            'Base path with trailing slash' => [
                'src/Controller/UserController.php',
                'src/',
                'App',
                'App\\src\\Controller',
            ],
            'Nested base path' => [
                'app/src/Controller/UserController.php',
                'app/src',
                'App',
                'App\\Controller',
            ],
        ];
    }

    public function testOperationIdToClassNameEdgeCases(): void
    {
        $result = $this->transformer->operationIdToClassName('complexString');
        $this->assertEquals('ComplexString', $result);

        $result = $this->transformer->operationIdToClassName('');
        $this->assertEquals('', $result);

        $result = $this->transformer->operationIdToClassName('a');
        $this->assertEquals('A', $result);
    }

    public function testGetDynamicNamespaceWithTestFiles(): void
    {
        $result = $this->transformer->getDynamicNamespace('tests/Controller/UserControllerTest.php', 'src', 'App');
        $this->assertEquals('App\\Tests\\Controller', $result);

        $result = $this->transformer->getDynamicNamespace('tests/foo/bar/UserControllerTest.php', 'src', 'App');
        $this->assertEquals('App\\Tests\\foo\\bar', $result);

        $result = $this->transformer->getDynamicNamespace('src/TestController/UserTest.php', 'src', 'App');
        $this->assertEquals('App\\TestController', $result);
    }

    public function testGetDynamicNamespaceWithVariousCases(): void
    {
        $result = $this->transformer->getDynamicNamespace(
            'src/Controller/UserController.php',
            'src',
            'App\\Core'
        );
        $this->assertEquals('App\\Core\\Controller', $result);

        $result = $this->transformer->getDynamicNamespace(
            'src/Core/SubCore/UserController.php',
            'src',
            'App\\Core'
        );
        $this->assertEquals('App\\Core\\SubCore', $result);

        $result = $this->transformer->getDynamicNamespace(
            'src/app/controller/UserController.php',
            'src',
            'App'
        );
        $this->assertEquals('App\\controller', $result);
    }

    public function testNormalizeNamespaceWithCaseDifferences(): void
    {
        $result = $this->transformer->getDynamicNamespace(
            'src/app/Controller/UserController.php',
            'src',
            'App'
        );

        $this->assertEquals('App\\Controller', $result);
    }

    public function testGetDynamicNamespaceWithAbsolutePath(): void
    {
        $absolutePath = '/var/www/html/src/Controller/UserController.php';
        $basePath = '/var/www/html/src';

        $result = $this->transformer->getDynamicNamespace($absolutePath, $basePath, 'App');
        $this->assertEquals('App\\Controller', $result);
    }

    public function testGetDynamicNamespaceWithEmptySegments(): void
    {
        $result = $this->transformer->getDynamicNamespace(
            'src//Controller///UserController.php',
            'src',
            'App'
        );

        $this->assertEquals('App\\Controller', $result);
    }
}
