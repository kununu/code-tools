<?php
declare(strict_types=1);

namespace CodeGenerator\Command;

use CodeGenerator\DTO\AnswersDTO;
use CodeGenerator\DTO\FileDTO;
use CodeGenerator\DTO\FilesDTO;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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
            'Request Resolver namespace? (e.g., \'Request\' will generate namespace App\Request\Resolver) Leave empty for default namespace App\Controller\Resolver:' => [
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
                        throw new RuntimeException('This field is required.');
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
        $filesDTO = new FilesDTO($files);

        $io = new SymfonyStyle($input, $output);
        $io->title('These are the files to be generated:');

        $sortedFiles = $filesDTO->getAllFiles();
        usort($sortedFiles, static fn(FileDTO $a, FileDTO $b) => strcmp($a->filePath, $b->filePath));

        foreach ($sortedFiles as $file) {
            $io->text($file->filePath);
        }

        if (!$io->confirm('Continue with file generation?')) {
            $output->writeln('File generation aborted.');

            return;
        }

        foreach ($sortedFiles as $file) {
            $output->writeln(sprintf('Generating %s...', $file->fileName));
            $this->generateFile($file, $answers, $filesDTO);
        }
    }

    private function getFilesList(AnswersDTO $answers): array
    {
        $namespacePrefix = 'Controller';
        $formattedControllerNamespace = $answers->controllerNamespace ? $namespacePrefix . '\\' . trim($answers->controllerNamespace, '\\') : $namespacePrefix;

        $useCaseName = ucfirst($answers->useCaseName);

        $controllerName = str_replace('\\', '/', $formattedControllerNamespace);
        $resolverNamespace = str_replace(
            '\\',
            '/',
            !empty($answers->requestResolverNamespace) ? trim($answers->requestResolverNamespace, '\\') : $formattedControllerNamespace
        );

        $files = match ($answers->generationType) {
            self::CONTROLLER => [
                new FileDTO(
                    fileName: 'Controller',
                    filePath: sprintf('src/%s/%sController.php', $controllerName, $useCaseName),
                    namespace: sprintf('App\\%s', $formattedControllerNamespace),
                    className: sprintf('%sController', $answers->useCaseName),
                    type: 'controller',
                    template: 'controller.twig',
                    fqcn: sprintf('App\\%s\\%sController', $formattedControllerNamespace, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'RequestDTO',
                    filePath: sprintf('src/%s/DTO/%s.php', $resolverNamespace, $useCaseName),
                    namespace: sprintf('App\\%s\\DTO', str_replace('/', '\\', $resolverNamespace)),
                    className: sprintf('%s', $answers->useCaseName),
                    type: 'request-dto',
                    template: 'request-dto.twig',
                    fqcn: sprintf('App\\%s\\DTO\\%s', str_replace('/', '\\', $resolverNamespace), $useCaseName)
                ),
                new FileDTO(
                    fileName: 'RequestResolver',
                    filePath: sprintf('src/%s/Resolver/%sResolver.php', $resolverNamespace, $useCaseName),
                    namespace: sprintf('App\\%s\\Resolver', str_replace('/', '\\', $resolverNamespace)),
                    className: sprintf('%sResolver', $answers->useCaseName),
                    type: 'resolver',
                    template: 'request-resolver.twig',
                    fqcn: sprintf('App\\%s\\Resolver\\%sResolver', str_replace('/', '\\', $resolverNamespace), $useCaseName)
                ),
                new FileDTO(
                    fileName: 'ValidationException',
                    filePath: sprintf('src/%s/Exception/ValidationException.php', $resolverNamespace),
                    namespace: sprintf('App\\%s\\Exception', str_replace('/', '\\', $resolverNamespace)),
                    className: 'ValidationException',
                    type: 'validation-exception',
                    template: 'request-validation-exception.twig',
                    fqcn: sprintf('App\\%s\\Exception\\ValidationException', str_replace('/', '\\', $resolverNamespace))
                ),
                new FileDTO(
                    fileName: 'services',
                    filePath: sprintf('src/UseCase/%s/%s/Resources/config/services.yaml', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\UseCase\%s\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%s/%s', $answers->useCaseType, $useCaseName),
                    type: 'services',
                    template: 'services.yaml.twig',
                    fqcn: sprintf('App\\UseCase\\%s\\%s\\Resources\\config\\services', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'readme',
                    filePath: sprintf('src/UseCase/%s/%s/README.md', $answers->useCaseType, $useCaseName),
                    namespace: '',
                    className: ucfirst($answers->useCaseName),
                    type: 'readme',
                    template: 'readme.twig',
                    fqcn: sprintf('App\\UseCase\\%s\\%s\\README', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'ControllerTest',
                    filePath: sprintf('tests/Functional/Controller/%s/%sControllerTest.php', $answers->controllerNamespace, $useCaseName),
                    namespace: sprintf('App\\Tests\\Functional\\Controller\\%s', $formattedControllerNamespace),
                    className: sprintf('%sControllerTest', $answers->useCaseName),
                    type: 'controller-test',
                    template: 'test-functional-controller.twig',
                    fqcn: sprintf('App\\Tests\\Functional\\Controller\\%s\\%sControllerTest', $answers->controllerNamespace, $useCaseName)
                ),
            ],
            default => throw new InvalidArgumentException('Invalid generation type.'),
        };

        $useCaseTypeSpecificFiles = match ($answers->useCaseType) {
            self::QUERY => [
                new FileDTO(
                    fileName: 'Query',
                    filePath: sprintf('src/UseCase/%s/%s/Query.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sQuery', $answers->useCaseName),
                    type: 'query',
                    template: 'query.twig',
                    fqcn: sprintf('App\\UseCase\\%s\\%s\\Query', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'QueryHandler',
                    filePath: sprintf('src/UseCase/%s/%s/QueryHandler.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sQueryHandler', $answers->useCaseName),
                    type: 'query-handler',
                    template: 'queryHandler.twig',
                    fqcn: sprintf('App\\UseCase\\%s\\%s\\QueryHandler', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'QueryTest',
                    filePath: sprintf('tests/Unit/UseCase/%s/%s/QueryTest.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sQueryTest', $answers->useCaseName),
                    type: 'query-test',
                    template: 'test-unit-query.twig',
                    fqcn: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s\\QueryTest', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'QueryHandlerTest',
                    filePath: sprintf('tests/Unit/UseCase/%s/%s/QueryHandlerTest.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sQueryHandlerTest', $answers->useCaseName),
                    type: 'query-handler-test',
                    template: 'test-unit-queryHandler.twig',
                    fqcn: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s\\QueryHandlerTest', $answers->useCaseType, $useCaseName)
                ),
            ],
            self::COMMAND => [
                new FileDTO(
                    fileName: 'Command',
                    filePath: sprintf('src/UseCase/%s/%s/Command.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sCommand', $answers->useCaseName),
                    type: 'command',
                    template: 'command.twig',
                    fqcn: sprintf('App\\UseCase\\%s\\%s\\Command', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'CommandHandler',
                    filePath: sprintf('src/UseCase/%s/%s/CommandHandler.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sCommandHandler', $answers->useCaseName),
                    type: 'command-handler',
                    template: 'commandHandler.twig',
                    fqcn: sprintf('App\\UseCase\\%s\\%s\\CommandHandler', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'CommandTest',
                    filePath: sprintf('tests/Unit/UseCase/%s/%s/CommandTest.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sCommandTest', $answers->useCaseName),
                    type: 'command-test',
                    template: 'test-unit-command.twig',
                    fqcn: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s\\CommandTest', $answers->useCaseType, $useCaseName)
                ),
                new FileDTO(
                    fileName: 'CommandHandlerTest',
                    filePath: sprintf('tests/Unit/UseCase/%s/%s/CommandHandlerTest.php', $answers->useCaseType, $useCaseName),
                    namespace: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s', $answers->useCaseType, $useCaseName),
                    className: sprintf('%sCommandHandlerTest', $answers->useCaseName),
                    type: 'command-handler-test',
                    template: 'test-unit-commandHandler.twig',
                    fqcn: sprintf('App\\Tests\\Unit\\UseCase\\%s\\%s\\CommandHandlerTest', $answers->useCaseType, $useCaseName)
                ),
            ],
            default => throw new InvalidArgumentException('Invalid generation type.'),
        };

        return array_merge($files, $useCaseTypeSpecificFiles);
    }

    private function generateFile(FileDTO $file, AnswersDTO $answers, FilesDTO $filesDTO): void
    {
        $formattedNamespace = rtrim($answers->controllerNamespace, '\\');

        $content = $this->twig->render($file->template, [
            'use_case_name'        => $answers->useCaseName,
            'use_case_type'        => $answers->useCaseType,
            'controller_namespace' => $formattedNamespace,
            'class_name'           => $answers->useCaseName,
            'resolver_namespace'   => $answers->requestResolverNamespace ?? $formattedNamespace,
            'file'                 => $file,
            'files'                => $filesDTO,
            'answers'              => $answers,
        ]);

        $this->filesystem->dumpFile($file->filePath, $content);
    }
}
