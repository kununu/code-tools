<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Infrastructure\Template\TwigTemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;

final class TwigTemplateRendererTest extends TestCase
{
    private Environment $twig;
    private TwigTemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->twig = new Environment(new ArrayLoader());
        $this->renderer = new TwigTemplateRenderer($this->twig);
    }

    #[DataProvider('renderTemplateDataProvider')]
    public function testRenderTemplate(string $template, array $variables, string $expected): void
    {
        $this->twig->setLoader(new ArrayLoader([
            $template => '{{ name }} {{ greeting }}',
        ]));

        $result = $this->renderer->renderTemplate($template, $variables);

        $this->assertEquals($expected, $result);
    }

    public static function renderTemplateDataProvider(): array
    {
        return [
            'Simple variables' => [
                'template.twig',
                ['name' => 'John', 'greeting' => 'Hello'],
                'John Hello',
            ],
            'Empty variables' => [
                'template.twig',
                [],
                ' ',
            ],
            'Complex variables' => [
                'template.twig',
                ['name' => 'John Doe', 'greeting' => 'Welcome to the system'],
                'John Doe Welcome to the system',
            ],
        ];
    }

    public function testRenderTemplateWithLoaderError(): void
    {
        $this->expectException(RuntimeException::class);

        $this->renderer->renderTemplate('non_existent.twig', []);
    }

    public function testRenderTemplateWithRuntimeError(): void
    {
        // Note: Modern versions of Twig don't throw a runtime error for undefined variables
        // in dev mode anymore. This test is verifying that our error handling works
        // so we'll use a different approach

        // Create a mock instead of using real Twig to ensure the exception is thrown
        $mockTwig = $this->createMock(Environment::class);
        $mockTwig->method('render')
            ->willThrowException(new RuntimeError('Variable "undefined_variable" does not exist'));

        $renderer = new TwigTemplateRenderer($mockTwig);

        $this->expectException(RuntimeException::class);

        $renderer->renderTemplate('error.twig', []);
    }

    public function testRenderTemplateWithSyntaxError(): void
    {
        // Adjust the test to match the actual exception message
        $this->twig->setLoader(new ArrayLoader([
            'syntax_error.twig' => '{% if %}',
        ]));

        $this->expectException(RuntimeException::class);

        $this->renderer->renderTemplate('syntax_error.twig', []);
    }

    public function testRegisterFilters(): void
    {
        $filterCalled = false;
        $filterCallback = function() use (&$filterCalled) {
            $filterCalled = true;

            return 'filtered';
        };

        $this->renderer->registerFilters([
            'test_filter' => $filterCallback,
        ]);

        $this->twig->setLoader(new ArrayLoader([
            'filter.twig' => '{{ "test"|test_filter }}',
        ]));

        $result = $this->renderer->renderTemplate('filter.twig', []);

        $this->assertTrue($filterCalled);
        $this->assertEquals('filtered', $result);
    }

    public function testRegisterMultipleFilters(): void
    {
        $filter1Called = false;
        $filter2Called = false;

        $filter1Callback = function() use (&$filter1Called) {
            $filter1Called = true;

            return 'filtered1';
        };

        $filter2Callback = function() use (&$filter2Called) {
            $filter2Called = true;

            return 'filtered2';
        };

        $this->renderer->registerFilters([
            'test_filter1' => $filter1Callback,
            'test_filter2' => $filter2Callback,
        ]);

        $this->twig->setLoader(new ArrayLoader([
            'filters.twig' => '{{ "test"|test_filter1|test_filter2 }}',
        ]));

        $result = $this->renderer->renderTemplate('filters.twig', []);

        $this->assertTrue($filter1Called);
        $this->assertTrue($filter2Called);
        $this->assertEquals('filtered2', $result);
    }
}
