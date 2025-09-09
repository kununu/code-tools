# Architecture Sniffer

## Overview

Architecture Sniffer enforces architectural and coding guidelines in PHP projects. It analyzes your codebase to ensure that dependency and structural rules are followed, helping maintain code quality and consistency.

## Quick Start

### Prerequisites

- PHP >= 8.3
- Composer
- This project (`code-tools`) should be installed as a dev dependency:
  ```bash
  composer require --dev kununu/code-tools
  ```

### Installation

Install via Composer as a dev dependency:

```bash
composer require --dev kununu/code-tools
```

### Minimal Configuration

Create an `architecture.yaml` in your `/services` directory:

```yaml
architecture:
  $controllers:
    includes:
      - "App\\Controller\\*Controller"
    depends_on:
      - "$services"
  $services:
    includes:
      - "App\\Service\\*Service"
```

## Usage

### Running with PHPStan

Add to your `phpstan.neon`:

```neon
includes:
    - vendor/phpat/phpat/extension.neon

parameters:
    phpat:
        ignore_built_in_classes: false
        show_rule_names: true

services:
    - class: Kununu\ArchitectureSniffer\ArchitectureSniffer
      tags:
        - phpat.test
```

Run analysis:

```bash
vendor/bin/phpstan analyse
```

### Standalone Usage

Refer to [PHPAT documentation](https://github.com/carlosas/phpat) for standalone usage.

## Configuration

The `architecture.yaml` file defines architectural groups and their dependencies. Each group represents a logical part of your application and specifies which other groups or classes it can depend on.

### Example Configuration

```yaml
architecture:
  $controllers:
    final: true
    extends: "$baseControllers"
    implements:
      - "App\\Controller\\ControllerInterface"
    must_only_have_one_public_method_named: "handle"
    includes:
      - "App\\Controller\\*Controller"
    depends_on:
      - "$services"
      - "$models"
      - "External\\Library\\SomeClass"
  $baseControllers:
    includes:
      - "App\\Controller\\Base\\*BaseController"
  $services:
    final: false
    implements:
      - "App\\Service\\ServiceInterface"
    includes:
      - "App\\Service\\*Service"
      - "$models"
    depends_on:
      - "$models"
  $models:
    includes:
      - "App\\Model\\*Model"
```

### Group Properties

Each group in your `architecture.yaml` configuration is defined as a key under `architecture`. Only `includes` is required; all other properties are optional and trigger specific architectural rules:

- **includes** (required):
  - List of patterns or group names that define which classes/interfaces belong to this group.
  - Example: `includes: ["App\\Controller\\*Controller"]`
  - **Rule triggered:** Classes matching these patterns are considered part of the group.

- **excludes** (optional):
  - List of patterns or group names to be excluded from all rule assertions for this group.
  - Example: `excludes: ["App\\Controller\\Abstract*", "App\\Service\\Legacy*"]`
  - This property is used for all rules (extends, implements, depends_on, must_not_depend_on, etc.).
  - **Note:** To blacklist dependencies, use `must_not_depend_on`.

- **depends_on** (optional):
  - List of group names or patterns that this group is allowed to depend on.
  - To prevent redundant dependencies, the rule will also consider all dependencies from "includes", "extends" and "implements".
  - Classes from the root namespace are also always included (e.g., `\DateTime`).
  - Example: `depends_on: ["services", "App\\Library\\*"]`
  - **Rule triggered:** Ensures that classes in this group only depend on allowed groups/classes. Violations are reported if dependencies are outside this list.
  - **Important:** If a group includes from a global namespace other than `App\\`, it must NOT have a `depends_on` property. This will cause a configuration error.

- **must_not_depend_on** (optional):
  - List of group names or patterns that this group is forbidden to depend on.
  - Example: `must_not_depend_on: ["$forbidden", "App\\Forbidden\\*"]`
  - **Rule triggered:** Reports any class in the group that depends on forbidden groups/classes.

- **final** (optional):
  - Boolean (`true`/`false`). If `true`, all classes in this group must be declared as `final`.
  - Example: `final: true`
  - **Rule triggered:** Reports any class in the group that is not declared as `final`.

- **extends** (optional):
  - Group name or class/interface that all classes in this group must extend.
  - Example: `extends: "$baseControllers"` or `extends: "App\\BaseController"`
  - **Rule triggered:** Reports any class in the group that does not extend the specified base class/group.

- **implements** (optional):
  - List of interfaces that all classes in this group must implement.
  - Example: `implements: ["App\\Controller\\ControllerInterface"]`
  - **Rule triggered:** Reports any class in the group that does not implement the required interfaces.

- **must_only_have_one_public_method_named** (optional):
  - String. Restricts classes in this group to only one public method with the specified name.
  - Example: `must_only_have_one_public_method_named: "handle"`
  - **Rule triggered:** Reports any class in the group that has more than one public method or a public method with a different name.

#### Summary Table
| Property                        | Required | Type      | Description                                                                 | Rule Triggered                                                      |
|----------------------------------|----------|-----------|-----------------------------------------------------------------------------|---------------------------------------------------------------------|
| includes                        | Yes      | array     | Patterns or group names for group membership                                | Group membership                                                    |
| excludes                        | No       | array     | Excludes for all rules in this group                                        | Exclusion from all rule assertions                                   |
| depends_on                      | No       | array     | Allowed dependencies                                                        | Dependency restriction                                              |
| must_not_depend_on              | No       | array     | Forbidden dependencies                                                      | Forbidden dependency restriction                                    |
| final                           | No       | boolean   | Require classes to be `final`                                               | Final class enforcement                                             |
| extends                         | No       | string    | Required base class/group                                                   | Inheritance enforcement                                             |
| implements                      | No       | array     | Required interfaces                                                         | Interface implementation enforcement                                |
| must_only_have_one_public_method_named | No | string    | Restrict to one public method with this name                                | Public method restriction                                           |

**Note:**
- Property names in YAML must use `snake_case` (e.g., `depends_on`), not camelCase.
- If a group includes from a global namespace other than `App\\`, do not define `depends_on` for that group.
- The configuration will fail with a clear error if these rules are violated.

### How Classes, Interfaces, and Namespaces Are Defined

When specifying patterns or references in your `architecture.yaml` (for `includes`, `depends_on`, etc.), the sniffer interprets them as follows:

- **Group Reference:**
  - If the string matches a group name defined elsewhere in your configuration, it is treated as a reference to that group. All selectables from that group are included.
  - Example: `"$services"` refers to the group named `$services`.

- **Namespace:**
  - If the string ends with a backslash (`\`), it is treated as a namespace. All classes within that namespace are matched.
  - Example: `"App\\Service\\"` matches everything in the `App\Service` namespace.

- **Interface:**
  - If the fqcn is a Interface or the regex ends with `Interface`, it is treated as an interface.
  - Example: `"App\\Service\\ServiceInterface"` matches the interface `ServiceInterface`.

- **Class:**
  - Any other string is treated as a fully qualified class name (FQCN).
  - Example: `"App\\Controller\\MyController"` matches the class `MyController`.

This logic applies to all properties that accept patterns or references, such as `includes`, `depends_on`, `extends`, and `implements`.

## Advanced Features

### Variable Referencing

- Groups are referenced by their name.
- The `$` prefix is recommended but not required.
- The reference must match the group name exactly.
- When referencing a group, all includes and excludes from that group are considered.
- Important: Includes overrule excludes, meaning if a exact namespace is listed in both include and exclude, it will only be part of the includes.
- Example:
  ```yaml
  architecture:
    $command_handler:
      includes:
        - "App\\Application\\Command\\*\\*Handler"
      depends_on:
        - "$write_repository"
    $write_repository:
      includes:
        - "App\\Repository\\*\\*RepositoryInterface"
      excludes:
        - "App\\Repository\\*\\*ReadOnlyRepositoryInterface"
  ```

### Pattern Matching

- Use backslashes for namespaces and `*` as a wildcard.
- Internally, `*` is converted to `.+` for regex matching.
- Example: `App\Controller\*Controller` becomes `/App\\Controller\\.+Controller/`.

## Troubleshooting & FAQ

- Ensure `architecture.yaml` is in your project root.
- Check for typos in group names and references.
- For a clean static analysis run, use:
  ```sh
  `php vendor/bin/phpstan clear-result && php vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 240M`
  ```
- For more help, see [PHPAT issues](https://github.com/carlosas/phpat/issues).

## Contributing

Contributions are welcome! Please submit issues or pull requests via GitHub.

## License

See [LICENSE](../LICENSE).

## Further Resources

- [PHPAT Documentation](https://github.com/carlosas/phpat)
- [Architecture Sniffer (Spryker)](https://github.com/spryker/architecture-sniffer)


