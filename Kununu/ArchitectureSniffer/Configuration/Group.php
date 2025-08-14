<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Generator;
use InvalidArgumentException;

final readonly class Group
{
    private const string NAME_KEY = 'name';
    public const string INCLUDES_KEY = 'includes';
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
        if (!array_key_exists(self::NAME_KEY, $data) || !array_key_exists(self::INCLUDES_KEY, $data)) {
            throw new InvalidArgumentException('Group configuration must contain "name" and "includes" keys.');
        }

        return new self(
            name: $name,
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
            yield new Rules\MustExtend(
                extensions: $library->getSelector($this->extends),
                selectables: $library->getSelectorsFromGroup($this->name),
            );
        }

        if ($this->implements) {
            yield new Rules\MustImplement(
                selectables: $library->getSelectorsFromGroup($this->name),
                interfaces: $library->getSelectors($this->implements),
            );
        }

        if ($this->final) {
            yield new Rules\MustBeFinal(
                selectables: $library->getSelectorsFromGroup($this->name),
            );
        }

        if ($this->dependsOn) {
            yield new Rules\MustOnlyDependOn(
                selectables: $library->getSelectorsFromGroup($this->name),
                dependencies: $library->getSelectors($this->dependsOn),
            );
        }

        if ($this->mustOnlyHaveOnePublicMethodNamed) {
            yield new Rules\MustOnlyHaveOnePublicMethodNamed(
                selectables: $library->getSelectorsFromGroup($this->name),
                functionName: $this->mustOnlyHaveOnePublicMethodNamed,
            );
        }
    }
}
