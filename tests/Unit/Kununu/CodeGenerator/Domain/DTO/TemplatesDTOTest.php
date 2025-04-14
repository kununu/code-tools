<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Domain\DTO;

use InvalidArgumentException;
use Kununu\CodeGenerator\Domain\DTO\TemplateDTO;
use Kununu\CodeGenerator\Domain\DTO\TemplatesDTO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('code-generator')]
final class TemplatesDTOTest extends TestCase
{
    public function testConstructorValidatesInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TemplatesDTO(['not-a-template-dto']);
    }

    public function testGetTemplateByType(): void
    {
        $template1 = $this->createTemplate('controller');
        $template2 = $this->createTemplate('query');

        $templatesDTO = new TemplatesDTO([$template1, $template2]);

        $this->assertSame($template1, $templatesDTO->getTemplateByType('controller'));
        $this->assertSame($template2, $templatesDTO->getTemplateByType('query'));
        $this->assertNull($templatesDTO->getTemplateByType('nonexistent'));
    }

    public function testGetAllTemplates(): void
    {
        $template1 = $this->createTemplate('controller');
        $template2 = $this->createTemplate('query');

        $templatesDTO = new TemplatesDTO([$template1, $template2]);
        $templates = $templatesDTO->getAllTemplates();

        $this->assertCount(2, $templates);
        $this->assertArrayHasKey('controller', $templates);
        $this->assertArrayHasKey('query', $templates);
        $this->assertSame($template1, $templates['controller']);
        $this->assertSame($template2, $templates['query']);
    }

    public function testGetTemplateTypes(): void
    {
        $template1 = $this->createTemplate('controller');
        $template2 = $this->createTemplate('query');

        $templatesDTO = new TemplatesDTO([$template1, $template2]);
        $types = $templatesDTO->getTemplateTypes();

        $this->assertCount(2, $types);
        $this->assertContains('controller', $types);
        $this->assertContains('query', $types);
    }

    public function testHasTemplate(): void
    {
        $template = $this->createTemplate('controller');

        $templatesDTO = new TemplatesDTO([$template]);

        $this->assertTrue($templatesDTO->hasTemplate('controller'));
        $this->assertFalse($templatesDTO->hasTemplate('nonexistent'));
    }

    #[DataProvider('caseInsensitiveTypeProvider')]
    public function testTypeCaseInsensitivity(string $registerType, string $lookupType): void
    {
        $template = $this->createTemplate($registerType);

        $templatesDTO = new TemplatesDTO([$template]);

        $this->assertTrue($templatesDTO->hasTemplate($lookupType));
        $this->assertSame($template, $templatesDTO->getTemplateByType($lookupType));
    }

    public static function caseInsensitiveTypeProvider(): array
    {
        return [
            'lowercase to uppercase'  => ['controller', 'CONTROLLER'],
            'uppercase to lowercase'  => ['CONTROLLER', 'controller'],
            'mixed case to lowercase' => ['ConTroller', 'controller'],
            'lowercase to mixed case' => ['controller', 'ConTroller'],
        ];
    }

    private function createTemplate(string $type): TemplateDTO
    {
        return new TemplateDTO(
            $type,
            'template.twig',
            ['key' => 'value'],
            'path/to/template.twig',
            'output/path.php',
            'App\\Namespace',
            'ClassName',
            'App\\Namespace\\ClassName'
        );
    }
}
