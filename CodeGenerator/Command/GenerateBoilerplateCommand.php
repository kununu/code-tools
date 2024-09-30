<?php
declare(strict_types=1);

namespace CodeGenerator\Command;

use CodeGenerator\DTO\AnswersDTO;
use CodeGenerator\DTO\FileDTO;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
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

        $this->generateFiles($answers, $input, $output);

        $output->writeln('Boilerplate generation completed.');

        return Command::SUCCESS;
    }

    private function askQuestions($helper, InputInterface $input, OutputInterface $output): AnswersDTO
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

        return new AnswersDTO(...$answers);
    }

    private function generateFiles(AnswersDTO $answers, InputInterface $input, OutputInterface $output): void
    {
        $files = $this->getFilesList($answers);

        $io = new SymfonyStyle($input, $output);
        $io->title('These are the files to be generated:');

        $sortedFiles = $files;
        usort($sortedFiles, static fn(FileDTO $a, FileDTO $b) => strcmp($a->filePath, $b->filePath));

        foreach ($sortedFiles as $file) {
            $io->text($file->filePath);
        }

        $confirm = new ConfirmationQuestion(
            'Continue with file generation?',
            true,
            '/^(y|j)/i'
        );

        if (!$io->askQuestion($confirm)) {
            $output->writeln('File generation aborted.');

            return;
        }

        foreach ($files as $file) {
            $output->writeln(sprintf('Generating %s...', $file->fileName));
            $this->generateFile($file, $answers);
        }
    }

    private function getFilesList(AnswersDTO $answers): array
    {
        $namespacePrefix = 'Controller';
        $formattedControllerNamespace = $answers->controllerNamespace ? $namespacePrefix . '\\' . trim($answers->controllerNamespace, '\\') : $namespacePrefix;

        $useCaseName = ucfirst($answers->useCaseName);

        $controllerName = str_replace('\\', '/', $formattedControllerNamespace);
        $files = match ($answers->generationType) {
            self::CONTROLLER => [
                new FileDTO('Controller.php', sprintf('src/%s/%sController.php', $controllerName, $useCaseName)),
                new FileDTO('services.yaml', sprintf('src/UseCase/%s/%s/Resources/config/services.yaml', $answers->useCaseType, $useCaseName)),
                new FileDTO('readme.md', sprintf('src/UseCase/%s/%s/README.md', $answers->useCaseType, $useCaseName)),
                new FileDTO('ControllerTest.php', sprintf('tests/Functional/Controller/%s/%sControllerTest.php', $answers->controllerNamespace, $useCaseName)),
            ],
            default => throw new InvalidArgumentException('Invalid generation type.'),
        };

        $useCaseTypeSpecificFiles = match ($answers->useCaseType) {
            self::QUERY => [
                new FileDTO('Query.php', sprintf('src/UseCase/%s/%s/Query.php', $answers->useCaseType, $useCaseName)),
                new FileDTO('QueryHandler.php', sprintf('src/UseCase/%s/%s/QueryHandler.php', $answers->useCaseType, $useCaseName)),
                new FileDTO('QueryTest.php', sprintf('tests/Unit/UseCase/%s/%s/QueryTest.php', $answers->useCaseType, $useCaseName)),
                new FileDTO('QueryHandlerTest.php', sprintf('tests/Unit/UseCase/%s/%s/QueryHandlerTest.php', $answers->useCaseType, $useCaseName)),
            ],
            self::COMMAND => [
                new FileDTO('Command.php', sprintf('src/UseCase/%s/%s/Command.php', $answers->useCaseType, $useCaseName)),
                new FileDTO('CommandHandler.php', sprintf('src/UseCase/%s/%s/CommandHandler.php', $answers->useCaseType, $useCaseName)),
                new FileDTO('CommandTest.php', sprintf('tests/Unit/UseCase/%s/%s/CommandTest.php', $answers->useCaseType, $useCaseName)),
                new FileDTO('CommandHandlerTest.php', sprintf('tests/Unit/UseCase/%s/%s/CommandHandlerTest.php', $answers->useCaseType, $useCaseName)),
            ],
            default => throw new InvalidArgumentException('Invalid generation type.'),
        };

        return array_merge($files, $useCaseTypeSpecificFiles);
    }

    private function generateFile(FileDTO $file, AnswersDTO $answers): void
    {
        $templateFileName = match (true) {
            str_contains($file->fileName, 'UnitTest')           => 'test-unit.twig',
            str_contains($file->fileName, 'FunctionalTest')     => 'test-functional.twig',
            str_contains($file->fileName, 'QueryTest')          => 'test-unit-query.twig',
            str_contains($file->fileName, 'QueryHandlerTest')   => 'test-unit-queryHandler.twig',
            str_contains($file->fileName, 'CommandTest')        => 'test-unit-command.twig',
            str_contains($file->fileName, 'CommandHandlerTest') => 'test-unit-commandHandler.twig',
            str_contains($file->fileName, 'ControllerTest')     => 'test-functional-controller.twig',
            str_contains($file->fileName, 'Controller')         => 'controller.twig',
            str_contains($file->fileName, 'CommandHandler')     => 'commandHandler.twig',
            str_contains($file->fileName, 'Command')            => 'command.twig',
            str_contains($file->fileName, 'QueryHandler')       => 'queryHandler.twig',
            str_contains($file->fileName, 'Query')              => 'query.twig',
            str_contains($file->fileName, 'services')           => 'services.yaml.twig',
            str_contains($file->fileName, 'readme')             => 'readme.twig',
            default                                             => throw new RuntimeException("Template for {$file->fileName} not found.")
        };

        $formattedNamespace = rtrim($answers->controllerNamespace, '\\');

        $content = $this->twig->render($templateFileName, [
            'use_case_name'        => $answers->useCaseName,
            'use_case_type'        => $answers->useCaseType,
            'controller_namespace' => $formattedNamespace,
            'class_name'           => $answers->useCaseName,
        ]);

        $this->filesystem->dumpFile($file->filePath, $content);
    }
}
