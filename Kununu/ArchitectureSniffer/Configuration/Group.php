<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Generator;
use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Rules\Rule;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;
use Kununu\ArchitectureSniffer\Configuration\Selector\SelectableCollection;

final class Group
{
    private const string NAME_KEY = 'name';
    public const string INCLUDES_KEY = 'includes';
    public const string DEPENDS_ON_KEY = 'depends_on';
    private const string FINAL_KEY = 'final';
    private const string EXTENDS_KEY = 'extends';
    private const string IMPLEMENTS_KEY = 'implements';
    private const string MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY = 'must_only_have_one_public_method_named';

    /**
     * @var array<Rule>
     */
    private array $rules = [];

    /**
     * @param array<Selectable>      $dependsOn
     * @param array<Selectable>|null $implements
     */
    private function __construct(
        private string $name,
        private SelectableCollection $includes,
        private ?array $dependsOn = null,
        private bool $final = false,
        private ?Selectable $extends = null,
        private ?array $implements = null,
        private ?string $mustOnlyHaveOnePublicMethodNamed = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        if (!array_key_exists(self::NAME_KEY, $data) || array_key_exists(self::INCLUDES_KEY, $data)) {
            throw new InvalidArgumentException('Group configuration must contain "name" and "includes" keys.');
        }

        return new self(
            name: $data[self::NAME_KEY],
            includes: SelectableCollection::fromArray($data[self::INCLUDES_KEY], $data[self::NAME_KEY]),
            dependsOn: $data[self::DEPENDS_ON_KEY] ?? null,
            final: $data[self::FINAL_KEY] ?? false,
            extends: $data[self::EXTENDS_KEY] ?? null,
            implements: $data[self::IMPLEMENTS_KEY] ?? null,
            mustOnlyHaveOnePublicMethodNamed: $data[self::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY] ?? null,
        );
    }

    public function generateRules(): Generator
    {
        if ($this->extends) {
            $this->rules[] = new Rules\MustExtend(
                extensions: SelectableCollection::toSelectable($this->extends),
                selectables: $this->includes->getSelectablesByGroup($this->name)
            );
        }

        if ($this->implements) {
            yield new Rules\MustImplement(
                selectables: $this->includes->getSelectablesByGroup($this->name),
                interfaces: SelectableCollection::toSelectable($this->implements),
            );
        }

        if ($this->final) {
            yield new Rules\MustBeFinal(
                selectables: $this->includes->getSelectablesByGroup($this->name),
            );
        }

        if ($this->dependsOn) {
            yield new Rules\MustOnlyDependOn(
                selectables: $this->includes->getSelectablesByGroup($this->name),
                dependencies: SelectableCollection::toSelectable($this->dependsOn),
            );
        }

        if ($this->mustOnlyHaveOnePublicMethodNamed) {
            yield new Rules\MustOnlyHaveOnePublicMethodNamed(
                selectables: $this->includes->getSelectablesByGroup($this->name),
                functionName: $this->mustOnlyHaveOnePublicMethodNamed,
            );
        }
    }

    public function getRules(): Generator
    {
        foreach ($this->rules as $rule) {
            yield $rule->getPHPatRule($this->name);
        }
    }
}
