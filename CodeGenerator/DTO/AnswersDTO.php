<?php
declare(strict_types=1);

namespace CodeGenerator\DTO;

final readonly class AnswersDTO
{
    public function __construct(
        public string $generationType,
        public string $useCaseName,
        public string $controllerNamespace,
        public string $requestResolverNamespace,
        public string $useCaseType
    ) {
    }
}
