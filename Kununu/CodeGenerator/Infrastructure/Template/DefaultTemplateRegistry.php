<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Domain\Service\Template\TemplatePathResolverInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplateRegistryInterface;

final class DefaultTemplateRegistry implements TemplateRegistryInterface
{
    private array $templates = [];
    private array $useCaseTemplates = [];

    public function __construct(private readonly TemplatePathResolverInterface $templatePathResolver)
    {
        $this->initializeUseCaseTemplates();
    }

    public function registerTemplate(string $templateName, string $templatePath, string $outputPattern): void
    {
        $resolvedTemplatePath = $this->templatePathResolver->resolveTemplatePath($templatePath);
        $this->templates[$templateName] = [
            'path'          => $resolvedTemplatePath,
            'original_path' => $templatePath,
            'outputPattern' => $outputPattern,
        ];
    }

    public function getAllTemplates(): array
    {
        return $this->templates;
    }

    public function shouldGenerateTemplate(string $templateName, array $configuration, array $variables): bool
    {
        $method = $variables['method'] ?? '';

        // Filter templates based on HTTP method
        if (!$this->isTemplateValidForMethod($templateName, $method)) {
            return false;
        }

        // Skip criteria template if there are no query parameters
        if ($this->shouldSkipCriteriaTemplate($templateName, $variables)) {
            return false;
        }

        // XML Serializer templates
        if ($templateName === 'query-serializer-xml' && strtoupper($method) !== 'GET') {
            return false;
        }

        // Check generators configuration
        if (!empty($configuration['generators'])) {
            return $this->isTemplateEnabledInConfiguration($templateName, $configuration);
        }

        return true;
    }

    private function isTemplateValidForMethod(string $templateName, string $method): bool
    {
        $queryTemplates = [
            'query',
            'query-handler',
            'criteria',
            'read-model',
            'query-repository-interface',
            'query-repository',
            'query-exception',
            'query-readme',
            'jms-serializer-config',
            'query-unit-test',
        ];

        $commandTemplates = [
            'command',
            'command-handler',
            'request-data',
            'request-resolver',
            'command-dto',
            'command-repository-interface',
            'command-repository',
            'command-readme',
            'command-unit-test',
        ];

        if (strtoupper($method) === 'GET' && in_array($templateName, $commandTemplates)) {
            return false;
        }

        if (strtoupper($method) !== 'GET' && in_array($templateName, $queryTemplates)) {
            return false;
        }

        return true;
    }

    private function shouldSkipCriteriaTemplate(string $templateName, array $variables): bool
    {
        return $templateName === 'criteria'
            && (empty($variables['parameters'])
                || empty(array_filter($variables['parameters'], static fn($param) => $param['in'] === 'query')));
    }

    private function isTemplateEnabledInConfiguration(string $templateName, array $configuration): bool
    {
        $generators = $configuration['generators'];

        // Check if use-case generation is disabled
        if (isset($generators['use-case'])
            && $generators['use-case'] === false
            && $this->isUseCaseTemplate($templateName)) {
            return false;
        }

        // Controller templates
        if ($templateName === 'controller'
            && isset($generators['controller'])
            && $generators['controller'] === false) {
            return false;
        }

        // DTO templates
        if (in_array($templateName, ['request-data', 'request-resolver'])
            && isset($generators['request-mapper'])
            && $generators['request-mapper'] === false) {
            return false;
        }

        // CQRS Command/Query templates
        if (in_array($templateName, ['command', 'command-handler', 'query', 'query-handler'])
            && isset($generators['cqrs-command-query'])
            && $generators['cqrs-command-query'] === false) {
            return false;
        }

        // Read Model templates
        if (in_array($templateName, ['read-model', 'query-serializer-xml', 'jms-serializer-config'])
            && isset($generators['read-model'])
            && $generators['read-model'] === false) {
            return false;
        }

        // Legacy Command templates check (for backward compatibility)
        if (in_array($templateName,
            [
                'command',
                'command-handler',
                'command-dto',
                'command-repository-interface',
                'command-repository',
            ]
        )
            && isset($generators['command'])
            && $generators['command'] === false) {
            return false;
        }

        // Repository templates
        if (in_array($templateName,
            [
                'query-repository-interface',
                'query-repository',
                'command-repository-interface',
                'command-repository',
                'query-infrastructure-query',
            ]
        )
            && isset($generators['repository'])
            && $generators['repository'] === false) {
            return false;
        }

        // XML or jms Serializer templates
        if (($templateName === 'query-serializer-xml' || $templateName === 'jms-serializer-config')
            && isset($generators['xml-serializer'])
            && $generators['xml-serializer'] === false) {
            return false;
        }

        // Test templates
        if (in_array($templateName, ['query-unit-test', 'command-unit-test', 'controller-functional-test'])
            && isset($generators['tests'])
            && $generators['tests'] === false) {
            return false;
        }

        return true;
    }

    private function isUseCaseTemplate(string $templateName): bool
    {
        return in_array($templateName, $this->useCaseTemplates);
    }

    private function initializeUseCaseTemplates(): void
    {
        $this->useCaseTemplates = [
            'query',
            'query-handler',
            'criteria',
            'read-model',
            'query-repository-interface',
            'query-repository',
            'query-exception',
            'query-readme',
            'jms-serializer-config',
            'query-unit-test',
            'command',
            'command-handler',
            'command-dto',
            'command-repository-interface',
            'command-repository',
            'command-readme',
            'command-unit-test',
            'query-serializer-xml',
            'query-infrastructure-query',
            'services-config',
        ];
    }
}
