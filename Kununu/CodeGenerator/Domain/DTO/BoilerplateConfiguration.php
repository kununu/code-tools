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
    public array $pathPatterns = [];
    public array $generators = [];
    public bool $force = false;
    public bool $skipExisting = false;
    public array $existingFiles = [];
    public array $skipFiles = [];
    public ?string $templateDir = null;

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

    public function setPathPatterns(array $patterns): self
    {
        $this->pathPatterns = $patterns;

        return $this;
    }

    public function setGenerators(array $generators): self
    {
        $this->generators = $generators;

        return $this;
    }

    public function setTemplateDir(?string $templateDir): self
    {
        $this->templateDir = $templateDir;

        return $this;
    }

    public function addTemplateVariable(string $name, mixed $value): self
    {
        $this->templateVariables[$name] = $value;

        return $this;
    }

    public function getTemplateVariables(): array
    {
        $variables = $this->templateVariables;

        // Only add CQRS type if operationDetails is set with a method
        if (isset($this->operationDetails['method'])) {
            $variables['cqrsType'] = $this->operationDetails['method'] === 'GET' ? 'Query' : 'Command';
        }

        return array_merge($variables, [
            'basePath' => $this->basePath,
        ]);
    }
}
