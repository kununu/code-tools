<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\Architecture;

use Kununu\CodeTools\Architecture\ArchitectureCheck;

trait RunsArchitectureCheck
{
    private const string EXPECTATION_SEPARATOR = ' should ';
    private const string VIOLATION_IDENTIFIER_PREFIX = 'phpat.';

    /** @var array<string, array{int, string, string}> */
    private static array $runs = [];

    private static function fixtureProject(string $name): string
    {
        return sprintf('%s/resources/Architecture/%s', dirname(__DIR__), $name);
    }

    /**
     * One run per project and options for the whole test class: a run takes seconds and its outcome does not change.
     *
     * @param list<string> $options
     *
     * @return array{int, string, string} exit code, report, and what was written as error output
     */
    private static function runCheck(string $project, array $options): array
    {
        return self::$runs[$project . implode('', $options)]
            ??= self::runCheckIn(sys_get_temp_dir(), $project, $options);
    }

    /**
     * @param list<string> $options
     *
     * @return array{int, string, string} exit code, report, and what was written as error output
     */
    private static function runCheckIn(string $temporaryDirectory, string $project, array $options): array
    {
        $errorOutput = '';
        $check = ArchitectureCheck::create(
            self::fixtureProject($project),
            $temporaryDirectory,
            static function(string $text) use (&$errorOutput): void {
                $errorOutput .= $text;
            }
        );

        ob_start();

        try {
            $exitCode = $check->run($options);
        } finally {
            $output = (string) ob_get_clean();
        }

        return [$exitCode, $output, $errorOutput];
    }

    /**
     * Reported errors as rule name => file => what the class should do, e.g. "not depend on App\Command\Foo".
     *
     * @return array<string, array<string, list<string>>>
     */
    private static function reportedViolations(string $project): array
    {
        [, $output] = self::runCheck($project, ['--error-format=json']);

        /** @var array{files: array<string, array{messages: list<array{message: string, identifier: string}>}>} $report */
        $report = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $projectPrefix = sprintf('%s/', realpath(self::fixtureProject($project)));

        $violations = [];
        foreach ($report['files'] as $file => $errors) {
            $relativeFile = str_replace($projectPrefix, '', $file);
            foreach ($errors['messages'] as $error) {
                $rule = str_replace(self::VIOLATION_IDENTIFIER_PREFIX, '', $error['identifier']);
                $violations[$rule][$relativeFile][] = explode(self::EXPECTATION_SEPARATOR, $error['message'], 2)[1];
            }
        }

        return self::sorted($violations);
    }

    /**
     * @param array<string, array<string, list<string>>> $violations
     *
     * @return array<string, array<string, list<string>>>
     */
    private static function sorted(array $violations): array
    {
        ksort($violations);
        foreach ($violations as &$files) {
            ksort($files);
            foreach ($files as &$expectations) {
                sort($expectations);
            }
        }

        return $violations;
    }
}
