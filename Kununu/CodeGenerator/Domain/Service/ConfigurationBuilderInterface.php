<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Symfony\Component\Console\Input\InputInterface;

interface ConfigurationBuilderInterface
{
    /**
     * Builds a complete BoilerplateConfiguration from various sources including
     * configuration files, command line options, and user input
     */
    public function buildConfiguration(InputInterface $input, string $configPath): BoilerplateConfiguration;
}
