<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;

interface CodeGeneratorInterface
{
    /**
     * Generate code based on configuration
     *
     * @param BoilerplateConfiguration $configuration Configuration object with all parameters
     *
     * @return array List of generated files
     */
    public function generate(BoilerplateConfiguration $configuration): array;

    /**
     * Get a list of files that would be generated without actually generating them
     *
     * @param BoilerplateConfiguration $configuration Configuration object with all parameters
     *
     * @return array List of files that would be generated
     */
    public function getFilesToGenerate(BoilerplateConfiguration $configuration): array;

    /**
     * Register a template for a specific file type
     *
     * @param string $templateName  Name of the template
     * @param string $templatePath  Path to the template file
     * @param string $outputPattern Pattern to determine the output file path
     *
     * @return void
     */
    public function registerTemplate(string $templateName, string $templatePath, string $outputPattern): void;
}
