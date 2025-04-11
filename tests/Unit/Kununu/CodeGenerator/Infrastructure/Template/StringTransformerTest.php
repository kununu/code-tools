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
            'Get operation'          => ['getUserProfile', 'User'],
            'Create operation'       => ['createUserProfile', 'User'],
            'Update operation'       => ['updateUserProfile', 'User'],
            'Delete operation'       => ['deleteUserProfile', 'User'],
            'Find operation'         => ['findUserProfile', 'User'],
            'List operation'         => ['listUserProfiles', 'User'],
            'With List suffix'       => ['getUserProfileList', 'User'],
            'With Collection suffix' => ['getUserProfileCollection', 'User'],
            'With Item suffix'       => ['getUserProfileItem', 'User'],
            'With By suffix'         => ['getUserProfileByEmail', 'User'],
            'Empty string'           => ['', ''],
            'No prefix or suffix'    => ['userProfile', 'User'],
            'Complex name'           => ['getToneOfVoiceSettings', 'Tone'],
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
        ];
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
        ];
    }
}
