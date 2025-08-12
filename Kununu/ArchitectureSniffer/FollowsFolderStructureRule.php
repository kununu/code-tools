<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

/**
 * @implements Rule<Node>
 */
final class FollowsFolderStructureRule implements Rule
{
    /**
     * @param array<int, mixed> $architectureLayers
     * @param array<int, mixed> $deprecatedLayers
     */
    public function __construct(
        private array $architectureLayers = [],
        private array $deprecatedLayers = [],
    ) {
        $archDefinition = DirectoryFinder::getArchitectureDefinition();

        foreach ($archDefinition['architecture'] as $layer) {
            $this->architectureLayers[] = $layer['layer'];
        }

        foreach ($archDefinition['deprecated'] as $layer) {
            $this->deprecatedLayers[] = $layer['layer'];
        }
    }

    public function getNodeType(): string
    {
        return Namespace_::class;
    }

    /**
     * @throws ShouldNotHappenException
     *
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $directories = array_merge($this->architectureLayers, $this->deprecatedLayers);
        $basePath = DirectoryFinder::getProjectDirectory() . '/src';

        $directory = glob($basePath . '/*');

        if ($directory === false) {
            return [
                RuleErrorBuilder::message("Directory does not exist $basePath/*")->build(),
            ];
        }

        $actualDirectories = array_filter($directory, 'is_dir');
        $actualNames = array_map('basename', $actualDirectories);

        // Check for extra directories
        $extraDirs = array_diff($actualNames, $directories);
        if (!empty($extraDirs)) {
            return [
                RuleErrorBuilder::message('Unexpected base directories found: ' . implode(', ', $extraDirs))
                    ->build(),
            ];
        }

        // Check for missing expected directories
        $missingDirs = array_diff($directories, $actualNames);
        if (!empty($missingDirs)) {
            return [
                RuleErrorBuilder::message('Missing expected base directories: ' . implode(', ', $missingDirs))
                    ->build(),
            ];
        }

        return [];
    }
}
