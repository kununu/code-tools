<?php
declare(strict_types=1);

namespace CodeGenerator\Command;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class GenerateBoilerplateCommand extends Command
{
    protected static $defaultName = 'app:generate:boilerplate';

    private Environment $twig;
    private Filesystem $filesystem;

    private const CONTROLLER = 'Controller';
    private const COMMAND = 'Command';
    private const QUERY = 'Query';

    public function __construct()
    {
        parent::__construct();

        $loader = new FilesystemLoader(__DIR__ . '/../Templates');
        $this->twig = new Environment($loader);
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this->setDescription('Generates boilerplate code based on templates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $answers = $this->askQuestions($helper, $input, $output);

        $this->generateFiles($answers, $output);

        $output->writeln('Boilerplate generation completed.');

        return Command::SUCCESS;
    }

    private function askQuestions($helper, InputInterface $input, OutputInterface $output): array
    {
        $questions = [
            'What are we generating? (Controller or Command)' => [
                'choices' => [self::CONTROLLER],
                'default' => self::CONTROLLER,
            ],
            'What is your UseCase name? (e.g., UpdateProfile)' => [
                'default'  => '',
                'required' => true,
            ],
            'Controller namespace? (e.g., \'Admin\' will generate namespace App\Controller\Admin) Leave empty for default namespace App\Controller:' => [
                'default' => '',
            ],
            'Please enter the use case type (Query or Command)' => [
                'choices' => [self::QUERY, self::COMMAND],
                'default' => self::QUERY,
            ],
        ];

        $answers = [];
        foreach ($questions as $question => $options) {
            if (isset($options['choices'])) {
                $questionObject = new ChoiceQuestion($question, $options['choices'], $options['default']);
            } else {
                $questionObject = new Question($question, $options['default']);
            }

            if (isset($options['required']) && $options['required']) {
                $questionObject->setValidator(static function($answer) {
                    if (empty($answer)) {
                        throw new RuntimeException('Answer is required.');
                    }

                    return $answer;
                });
            }
            $answers[] = $helper->ask($input, $output, $questionObject);
        }

        return $answers;
    }

    private function generateFiles(array $answers, OutputInterface $output): void
    {
        [$generationType, $useCaseName, $controllerNamespace, $useCaseType] = $answers;

        // Prepare formatted controller namespace path without double backslashes
        $namespacePrefix = 'Controller';
        $formattedControllerNamespace = $controllerNamespace ? $namespacePrefix . '\\' . trim($controllerNamespace, '\\') : $namespacePrefix;

        $useCaseName = ucfirst($useCaseName);

        $controllerName = str_replace('\\', '/', $formattedControllerNamespace);
        $files = match ($generationType) {
            self::CONTROLLER => [
                'Controller.php'     => sprintf('src/%s/%sController.php', $controllerName, $useCaseName),
                'services.yaml'      => sprintf('src/UseCase/%s/%s/Resources/config/services.yaml', $useCaseType, $useCaseName),
                'readme.md'          => sprintf('src/UseCase/%s/%s/README.md', $useCaseType, $useCaseName),
                'ControllerTest.php' => sprintf('tests/Functional/Controller/%s/%sControllerTest.php', $useCaseName, $useCaseName),
            ],
            default => throw new InvalidArgumentException('Invalid generation type.'),
        };

        $useCaseTypeSpecificFiles = match ($useCaseType) {
            self::QUERY => [
                'Query.php'            => sprintf('src/UseCase/%s/%s/Query.php', $useCaseType, $useCaseName),
                'QueryHandler.php'     => sprintf('src/UseCase/%s/%s/QueryHandler.php', $useCaseType, $useCaseName),
                'QueryTest.php'        => sprintf('tests/Unit/UseCase/%s/%s/QueryTest.php', $useCaseType, $useCaseName),
                'QueryHandlerTest.php' => sprintf('tests/Unit/UseCase/%s/%s/QueryHandlerTest.php', $useCaseType, $useCaseName),
            ],
            self::COMMAND => [
                'Command.php'            => sprintf('src/UseCase/%s/%s/Command.php', $useCaseType, $useCaseName),
                'CommandHandler.php'     => sprintf('src/UseCase/%s/%s/CommandHandler.php', $useCaseType, $useCaseName),
                'CommandTest.php'        => sprintf('tests/Unit/UseCase/%s/%s/CommandTest.php', $useCaseType, $useCaseName),
                'CommandHandlerTest.php' => sprintf('tests/Unit/UseCase/%s/%s/CommandHandlerTest.php', $useCaseType, $useCaseName),
            ],
            default => throw new InvalidArgumentException('Invalid generation type.'),
        };

        $files = array_merge($files, $useCaseTypeSpecificFiles);

        foreach ($files as $fileName => $filePath) {
            $output->writeln(sprintf('Generating %s...', $fileName));
            $this->generateFile($fileName, $filePath, $useCaseName, $useCaseType, $formattedControllerNamespace);
        }
    }

    private function generateFile(string $fileName, string $filePath, string $useCaseName, string $useCaseType, string $controllerNamespace): void
    {
        $templateFileName = match (true) {
            str_contains($fileName, 'UnitTest')           => 'test-unit.twig',
            str_contains($fileName, 'FunctionalTest')     => 'test-functional.twig',
            str_contains($fileName, 'QueryTest')          => 'test-unit-query.twig',
            str_contains($fileName, 'QueryHandlerTest')   => 'test-unit-queryHandler.twig',
            str_contains($fileName, 'CommandTest')        => 'test-unit-command.twig',
            str_contains($fileName, 'CommandHandlerTest') => 'test-unit-commandHandler.twig',
            str_contains($fileName, 'ControllerTest')     => 'test-functional-controller.twig',
            str_contains($fileName, 'Controller')         => 'controller.twig',
            str_contains($fileName, 'CommandHandler')     => 'commandHandler.twig',
            str_contains($fileName, 'Command')            => 'command.twig',
            str_contains($fileName, 'QueryHandler')       => 'queryHandler.twig',
            str_contains($fileName, 'Query')              => 'query.twig',
            str_contains($fileName, 'services')           => 'services.yaml.twig',
            str_contains($fileName, 'readme')             => 'readme.twig',
            default                                       => throw new RuntimeException("Template for {$fileName} not found.")
        };

        // Correctly format the controller namespace
        $formattedNamespace = rtrim($controllerNamespace, '\\');

        // Render the template with controller namespace as well
        $content = $this->twig->render($templateFileName, [
            'use_case_name'        => $useCaseName,
            'use_case_type'        => $useCaseType,
            'controller_namespace' => $formattedNamespace,
            'class_name'           => $useCaseName,
        ]);

        $this->filesystem->dumpFile($filePath, $content);
    }
}
