<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Domain\Service\Template\TemplateRenderingServiceInterface;
use RuntimeException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TwigFilter;

final class TwigTemplateRenderer implements TemplateRenderingServiceInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function renderTemplate(string $templatePath, array $variables): string
    {
        try {
            return $this->twig->render($templatePath, $variables);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            throw new RuntimeException(
                sprintf('Error rendering template %s: %s', $templatePath, $e->getMessage()), 0, $e);
        }
    }

    public function registerFilters(array $filters): void
    {
        foreach ($filters as $name => $callback) {
            $this->twig->addFilter(new TwigFilter($name, $callback));
        }
    }
}
