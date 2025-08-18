<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Generator;
use InvalidArgumentException;

final readonly class Group
{
    public const string INCLUDES_KEY = 'includes';
    public const string EXCLUDES_KEY = 'excludes';
    public const string DEPENDS_ON_KEY = 'depends_on';
    private const string FINAL_KEY = 'final';
    private const string EXTENDS_KEY = 'extends';
    private const string IMPLEMENTS_KEY = 'implements';
    private const string MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY = 'must_only_have_one_public_method_named';

    /**
     * @param array<string>|null $dependsOn
     * @param array<string>|null $implements
     */
    private function __construct(
        private string $name,
        private ?array $excludes = null,
        private ?array $dependsOn = null,
        private bool $final = false,
        private ?string $extends = null,
        private ?array $implements = null,
        private ?string $mustOnlyHaveOnePublicMethodNamed = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $name, array $data): self
    {
        if (!array_key_exists(self::INCLUDES_KEY, $data)) {
            throw new InvalidArgumentException('Group configuration must contain "includes" key.');
        }

        return new self(
            name: $name,
            excludes: $data[self::EXCLUDES_KEY] ?? null,
            dependsOn: $data[self::DEPENDS_ON_KEY] ?? null,
            final: $data[self::FINAL_KEY] ?? false,
            extends: $data[self::EXTENDS_KEY] ?? null,
            implements: $data[self::IMPLEMENTS_KEY] ?? null,
            mustOnlyHaveOnePublicMethodNamed: $data[self::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY] ?? null,
        );
    }

    public function getRules(SelectorsLibrary $library): Generator
    {
        if ($this->extends) {
            yield Rules\MustExtend::fromGenerators(
                extensions: $library->getSelector($this->extends),
                selectables: $library->getSelectorsFromGroup($this->name),
            )->getPHPatRule($this->name);
        }

        if ($this->implements) {
            yield Rules\MustImplement::fromGenerators(
                selectables: $library->getSelectorsFromGroup($this->name),
                interfaces: $library->getSelectors($this->implements),
            )->getPHPatRule($this->name);
        }

        if ($this->final) {
            yield Rules\MustBeFinal::fromGenerator(
                selectables: $library->getSelectorsFromGroup($this->name),
            )->getPHPatRule($this->name);
        }

        if ($this->dependsOn) {
            yield Rules\MustOnlyDependOn::fromGenerators(
                selectables: $library->getSelectorsFromGroup($this->name),
                dependencies: $library->getSelectors($this->dependsOn),
                extends: $this->extends ? $library->getSelector($this->extends) : null,
                implements: $this->implements ? $library->getSelectors($this->implements) : null,
                excludes: $this->excludes ? $library->getSelectors($this->excludes) : null,
            )->getPHPatRule($this->name);
        }

        if ($this->mustOnlyHaveOnePublicMethodNamed) {
            yield Rules\MustOnlyHaveOnePublicMethodNamed::fromGenerator(
                selectables: $library->getSelectorsFromGroup($this->name),
                functionName: $this->mustOnlyHaveOnePublicMethodNamed,
            )->getPHPatRule($this->name);
        }
    }
}
