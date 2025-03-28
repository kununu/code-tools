<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\DTO;

final class BoilerplateConfiguration
{
    public ?string $openApiFilePath = null;
    public ?string $operationId = null;
    public ?array $operationDetails = null;
    public string $basePath = 'src';
    public string $namespace = 'App';
    public array $templateVariables = [];
    /** @var array<string, string> Map of template names to custom path patterns */
    public array $pathPatterns = [];
    /** @var array<string, bool> Map of generator types to enabled/disabled status */
    public array $generators = [];
    /** @var bool Whether to force overwrite existing files without confirmation */
    public bool $force = false;
    /** @var bool Whether to skip all existing files without confirmation */
    public bool $skipExisting = false;
    /** @var array<string> List of files that already exist */
    public array $existingFiles = [];
    /** @var array<string> List of files to skip (don't overwrite) */
    public array $skipFiles = [];

    public function setOpenApiFilePath(?string $path): self
    {
        $this->openApiFilePath = $path;

        return $this;
    }

    public function setOperationId(?string $operationId): self
    {
        $this->operationId = $operationId;

        return $this;
    }

    public function setForce(bool $force): self
    {
        $this->force = $force;

        return $this;
    }

    public function setSkipExisting(bool $skipExisting): self
    {
        $this->skipExisting = $skipExisting;

        return $this;
    }

    public function addSkipFile(string $filePath): self
    {
        $this->skipFiles[] = $filePath;

        return $this;
    }

    public function setOperationDetails(?array $details): self
    {
        $this->operationDetails = $details;

        if ($details !== null) {
            // Extract common variables from operation details
            $this->templateVariables = array_merge($this->templateVariables, [
                'operation_id' => $details['id'] ?? '',
                'summary'      => $details['summary'] ?? '',
                'description'  => $details['description'] ?? '',
                'path'         => $details['path'] ?? '',
                'method'       => $details['method'] ?? '',
                'request_body' => $details['requestBody'] ?? null,
                'responses'    => $details['responses'] ?? [],
                'parameters'   => $details['parameters'] ?? [],
                'tags'         => $details['tags'] ?? [],
            ]);
        }

        return $this;
    }

    public function setBasePath(string $path): self
    {
        $this->basePath = $path;

        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        $this->templateVariables['namespace'] = $namespace;

        return $this;
    }

    /**
     * Set custom path patterns for templates from the configuration
     *
     * @param array<string, string> $patterns Map of template names to path patterns
     */
    public function setPathPatterns(array $patterns): self
    {
        $this->pathPatterns = $patterns;

        return $this;
    }

    /**
     * Set generators configuration from the configuration file
     *
     * @param array<string, bool> $generators Map of generator types to enabled/disabled status
     */
    public function setGenerators(array $generators): self
    {
        $this->generators = $generators;

        return $this;
    }

    public function addTemplateVariable(string $name, mixed $value): self
    {
        $this->templateVariables[$name] = $value;

        return $this;
    }

    public function getTemplateVariables(): array
    {
        return array_merge($this->templateVariables, [
            'basePath' => $this->basePath,
            'cqrsType' => $this->operationDetails['method'] === 'GET' ? 'Query' : 'Command',
        ]);
    }
}
