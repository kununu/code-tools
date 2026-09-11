<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\ArchitectureSniffer;
use Kununu\CodeTools\ArchitectureSniffer\Helper\ProjectPathResolver;
use PHPat\Rule\Assertion\Declaration\ShouldBeFinal\ShouldBeFinal;
use PHPat\Rule\Assertion\Relation\CanOnlyDepend\CanOnlyDepend;
use PHPat\Test\Builder\Rule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ArchitectureSnifferTest extends TestCase
{
    private string $architectureFile;
    private string $architectureDir;

    public function testTestArchitectureYieldsRulesForValidConfig(): void
    {
        $this->writeYaml([
            'architecture' => [
                'services' => [
                    'includes'   => ['App\\Service\\MyService'],
                    'depends_on' => ['App\\Repository\\'],
                    'final'      => true,
                ],
            ],
        ]);

        $sniffer = new ArchitectureSniffer();
        $rules = iterator_to_array($sniffer->testArchitecture());

        $assertions = array_map(static fn(Rule $rule): ?string => $rule()->assertion, $rules);

        self::assertEquals([ShouldBeFinal::class, CanOnlyDepend::class], $assertions);
    }

    public function testTestArchitectureThrowsWhenArchitectureKeyMissing(): void
    {
        $this->writeYaml([
            'something_else' => [],
        ]);

        $sniffer = new ArchitectureSniffer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('"architecture" key is missing');

        iterator_to_array($sniffer->testArchitecture());
    }

    public function testTestArchitectureThrowsWhenGroupsNotStringKeyed(): void
    {
        $this->writeYaml([
            'architecture' => ['not-string-keyed'],
        ]);

        $sniffer = new ArchitectureSniffer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('"groups" must be a non-empty array');

        iterator_to_array($sniffer->testArchitecture());
    }

    public function testTestArchitectureThrowsWhenNoGroupHasIncludes(): void
    {
        $this->writeYaml([
            'architecture' => [
                'services' => [
                    'final' => true,
                ],
            ],
        ]);

        $sniffer = new ArchitectureSniffer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('"includes" property');

        iterator_to_array($sniffer->testArchitecture());
    }

    public function testTestArchitectureThrowsWhenNoGroupHasDependsOn(): void
    {
        $this->writeYaml([
            'architecture' => [
                'services' => [
                    'includes' => ['App\\Service\\MyService'],
                    'final'    => true,
                ],
            ],
        ]);

        $sniffer = new ArchitectureSniffer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('"dependsOn" property');

        iterator_to_array($sniffer->testArchitecture());
    }

    public function testTestArchitectureThrowsWhenGlobalNamespaceGroupHasDependsOn(): void
    {
        $this->writeYaml([
            'architecture' => [
                'services' => [
                    'includes'   => ['App\\Service\\MyService'],
                    'depends_on' => ['App\\Repository\\'],
                ],
                'external' => [
                    'includes'   => ['Vendor\\Package\\SomeClass'],
                    'depends_on' => ['App\\Service\\'],
                ],
            ],
        ]);

        $sniffer = new ArchitectureSniffer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('global namespace');

        iterator_to_array($sniffer->testArchitecture());
    }

    public function testTestArchitectureWithGlobalNamespaceWithoutDependsOn(): void
    {
        $this->writeYaml([
            'architecture' => [
                'services' => [
                    'includes'   => ['App\\Service\\MyService'],
                    'depends_on' => ['App\\Repository\\'],
                ],
                'external' => [
                    'includes' => ['Vendor\\Package\\SomeClass'],
                ],
            ],
        ]);

        $sniffer = new ArchitectureSniffer();
        $rules = iterator_to_array($sniffer->testArchitecture());

        self::assertNotEmpty($rules);
    }

    protected function setUp(): void
    {
        $this->architectureFile = ProjectPathResolver::resolve('architecture.yaml');
        $this->architectureDir = dirname($this->architectureFile);

        if (!is_dir($this->architectureDir)) {
            mkdir($this->architectureDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->architectureFile)) {
            unlink($this->architectureFile);
        }
        if (is_dir($this->architectureDir)) {
            @rmdir($this->architectureDir);
        }
    }

    /** @param array<string, mixed> $data */
    private function writeYaml(array $data): void
    {
        file_put_contents($this->architectureFile, Yaml::dump($data, 4));
    }
}
