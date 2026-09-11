<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Helper;

use Kununu\CodeTools\ArchitectureSniffer\Helper\ProjectPathResolver;
use PHPUnit\Framework\TestCase;

final class ProjectPathResolverTest extends TestCase
{
    public function testResolveReturnsPathEndingWithFileName(): void
    {
        $result = ProjectPathResolver::resolve('architecture.yaml');

        self::assertStringEndsWith('/services/architecture.yaml', $result);
    }

    public function testResolveReturnsAbsolutePath(): void
    {
        $result = ProjectPathResolver::resolve('test.txt');

        self::assertStringStartsWith('/', $result);
        self::assertStringEndsWith('/services/test.txt', $result);
    }
}
