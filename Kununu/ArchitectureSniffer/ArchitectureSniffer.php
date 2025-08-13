<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer;

use Kununu\ArchitectureSniffer\Helper\ProjectPathResolver;
use PHPat\Test\Builder\Rule as PHPatRule;
use Symfony\Component\Yaml\Yaml;

final class ArchitectureSniffer
{
    private const string ARCHITECTURE_FILENAME = 'architecture.yaml';

    /**
     * @return iterable<PHPatRule>
     */
    public function testArchitecture(): iterable
    {
        $architecture = $this->getArchitecture();
        foreach ($architecture->getGroups() as $group) {
            foreach ($group->getRules() as $rule) {
                yield $rule;
            }
        }
    }

    private function getArchitecture(): Architecture
    {
        return Architecture::fromArray(
            Yaml::parseFile(ProjectPathResolver::resolve(self::ARCHITECTURE_FILENAME))
        );
    }
}
