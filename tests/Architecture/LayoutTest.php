<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\Architecture;

use Kununu\CodeTools\Architecture\Layout;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LayoutTest extends TestCase
{
    use RunsArchitectureCheck;

    #[DataProvider('fromSourceDirectoryDataProvider')]
    public function testFromSourceDirectory(string $project, Layout $expected): void
    {
        self::assertEquals($expected, Layout::fromSourceDirectory(self::fixtureProject($project) . '/src'));
    }

    /** @return array<string, array{string, Layout}> */
    public static function fromSourceDirectoryDataProvider(): array
    {
        return [
            'use_case_folder_wins_over_service_folder' => ['cqrs', Layout::Cqrs],
            'application_folder'                       => ['hexagonal', Layout::Hexagonal],
            'service_folder'                           => ['service-layered', Layout::ServiceLayered],
            'none_of_the_known_folders'                => ['clean', Layout::Unknown],
            'missing_directory'                        => ['does-not-exist', Layout::Unknown],
        ];
    }
}
