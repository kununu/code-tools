<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Kununu\ArchitectureSniffer\Helper\TypeChecker;

final readonly class Group
{
    public const string INCLUDES_KEY = 'includes';
    public const string EXCLUDES_KEY = 'excludes';
    public const string DEPENDS_ON_KEY = 'depends_on';
    public const string FINAL_KEY = 'final';
    public const string EXTENDS_KEY = 'extends';
    public const string IMPLEMENTS_KEY = 'implements';
    public const string MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY = 'must_only_have_one_public_method_named';
    public const string MUST_NOT_DEPEND_ON_KEY = 'must_not_depend_on';

    /**
     * @param string[]      $flattenedIncludes
     * @param string[]|null $flattenedExcludes
     * @param string[]|null $implements
     * @param string[]|null $mustNotDependOn
     * @param string[]|null $dependsOn
     */
    public function __construct(
        public string $name,
        public array $flattenedIncludes,
        public ?array $flattenedExcludes,
        public ?array $dependsOn,
        public ?array $mustNotDependOn,
        public ?string $extends,
        public ?array $implements,
        public bool $isFinal,
        public ?string $mustOnlyHaveOnePublicMethodName,
    ) {
    }

    /**
     * @param string[]                            $flattenedIncludes
     * @param array<string, string|bool|string[]> $targetAttributes
     * @param string[]|null                       $flattenedExcludes
     */
    public static function buildFrom(
        string $groupName,
        array $flattenedIncludes,
        array $targetAttributes,
        ?array $flattenedExcludes,
    ): self {
        return new self(
            name: $groupName,
            flattenedIncludes: $flattenedIncludes,
            flattenedExcludes: $flattenedExcludes,
            dependsOn: $targetAttributes[self::DEPENDS_ON_KEY] ?
                TypeChecker::castArrayOfStrings($targetAttributes[self::DEPENDS_ON_KEY]) : null,
            mustNotDependOn: $targetAttributes[self::MUST_NOT_DEPEND_ON_KEY] ?
                TypeChecker::castArrayOfStrings($targetAttributes[self::MUST_NOT_DEPEND_ON_KEY]) : null,
            extends: is_string($targetAttributes[self::EXTENDS_KEY]) ? $targetAttributes[self::EXTENDS_KEY] : null,
            implements: $targetAttributes[self::IMPLEMENTS_KEY] ?
                TypeChecker::castArrayOfStrings($targetAttributes[self::IMPLEMENTS_KEY]) : null,
            isFinal: $targetAttributes[self::FINAL_KEY] === true,
            mustOnlyHaveOnePublicMethodName: is_string($targetAttributes[self::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY]) ?
                $targetAttributes[self::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY] : null,
        );
    }

    public function shouldBeFinal(): bool
    {
        return $this->isFinal;
    }

    public function shouldExtend(): bool
    {
        return $this->extends !== null;
    }

    public function shouldNotDependOn(): bool
    {
        return $this->mustNotDependOn !== null && count($this->mustNotDependOn) > 0;
    }

    public function shouldDependOn(): bool
    {
        return $this->dependsOn !== null && count($this->dependsOn) > 0;
    }

    public function shouldImplement(): bool
    {
        return $this->implements !== null && count($this->implements) > 0;
    }

    public function shouldOnlyHaveOnePublicMethodNamed(): bool
    {
        return $this->mustOnlyHaveOnePublicMethodName !== null && $this->mustOnlyHaveOnePublicMethodName !== '';
    }
}
