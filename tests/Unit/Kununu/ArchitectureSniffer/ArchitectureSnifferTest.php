<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\ArchitectureSniffer;
use Kununu\ArchitectureSniffer\Helper\ProjectPathResolver;
use PHPat\Test\Builder\Rule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ArchitectureSnifferTest extends TestCase
{
    private string $architectureFile;
    private string $architectureDir;

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

        self::assertNotEmpty($rules);
        foreach ($rules as $rule) {
            self::assertInstanceOf(Rule::class, $rule);
        }
    }

    public function testTestArchitectureThrowsWhenArchitectureKeyMissing(): void
    {
        $this->writeYaml([
            'something_else' => [],
        ]);

        $sniffer = new ArchitectureSniffer();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('"architecture" key is missing');

        iterator_to_array($sniffer->testArchitecture());
    }

    public function testTestArchitectureThrowsWhenGroupsNotStringKeyed(): void
    {
        $this->writeYaml([
            'architecture' => ['not-string-keyed'],
        ]);

        $sniffer = new ArchitectureSniffer();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('"groups" must be a non-empty array');

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

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('"includes" property');

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

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('"dependsOn" property');

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

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('global namespace');

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

    private function writeYaml(array $data): void
    {
        file_put_contents($this->architectureFile, Yaml::dump($data, 4));
    }
}
