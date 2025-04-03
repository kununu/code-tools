<?php

declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Domain\DTO;

use Kununu\CodeGenerator\Domain\DTO\TemplateDTO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('code-generator')]
final class TemplateDTOTest extends TestCase
{
    public function testRequiredPropertiesAreSet(): void
    {
        $dto = new TemplateDTO(
            'controller',
            'controller.twig'
        );

        $this->assertSame('controller', $dto->type);
        $this->assertSame('controller.twig', $dto->template);
        $this->assertSame([], $dto->templateVariables);
        $this->assertNull($dto->path);
        $this->assertNull($dto->outputPath);
        $this->assertNull($dto->namespace);
        $this->assertNull($dto->classname);
        $this->assertNull($dto->fqcn);
        $this->assertNull($dto->filename);
        $this->assertNull($dto->dirname);
    }

    public function testAllPropertiesAreSet(): void
    {
        $dto = new TemplateDTO(
            'controller',
            'controller.twig',
            ['var' => 'value'],
            'templates/controller.twig',
            'src/Controller/UserController.php',
            'App\\Controller',
            'UserController',
            'App\\Controller\\UserController',
            'UserController.php',
            'src/Controller'
        );

        $this->assertSame('controller', $dto->type);
        $this->assertSame('controller.twig', $dto->template);
        $this->assertSame(['var' => 'value'], $dto->templateVariables);
        $this->assertSame('templates/controller.twig', $dto->path);
        $this->assertSame('src/Controller/UserController.php', $dto->outputPath);
        $this->assertSame('App\\Controller', $dto->namespace);
        $this->assertSame('UserController', $dto->classname);
        $this->assertSame('App\\Controller\\UserController', $dto->fqcn);
        $this->assertSame('UserController.php', $dto->filename);
        $this->assertSame('src/Controller', $dto->dirname);
    }

    /**
     * @dataProvider templateVariablesProvider
     */
    public function testVariousTemplateVariables(array $variables): void
    {
        $dto = new TemplateDTO('type', 'template.twig', $variables);

        $this->assertSame($variables, $dto->templateVariables);
    }

    public static function templateVariablesProvider(): array
    {
        return [
            'empty'   => [[]],
            'simple'  => [['key' => 'value']],
            'nested'  => [['parent' => ['child' => 'value']]],
            'complex' => [[
                'string' => 'value',
                'int'    => 42,
                'bool'   => true,
                'null'   => null,
                'array'  => [1, 2, 3],
                'object' => ['prop' => 'value'],
            ]],
        ];
    }

    public function testFqcnCorrespondsToNamespaceAndClassname(): void
    {
        $namespace = 'App\\Controller';
        $classname = 'UserController';
        $expectedFqcn = 'App\\Controller\\UserController';

        $dto = new TemplateDTO(
            'controller',
            'controller.twig',
            [],
            null,
            null,
            $namespace,
            $classname,
            $expectedFqcn
        );

        $this->assertSame($expectedFqcn, $dto->fqcn);
        $this->assertSame("$namespace\\$classname", $dto->fqcn);
    }
}
