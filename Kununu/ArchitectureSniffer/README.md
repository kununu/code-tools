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
  - name: "$controllers"
    includes:
      - "App\Controller\*Controller"
    depends_on:
      - "$services"

  - name: "$services"
    includes:
      - "App\Service\*Service"
```

## Usage

### Running with PHPStan

Add to your `phpstan.neon`:

```neon
includes:
    - vendor/carlosas/phpat/extension.neon

services:
    -
        class: PHPAT\PHPStan\PHPStanExtension
        tags: [phpstan.extension]
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
  - name: "$controllers"
    final: true
    extends: "$baseControllers"
    implements:
      - "App\Controller\ControllerInterface"
    must_only_have_one_public_method_named: "handle"
    includes:
      - "App\Controller\*Controller"
    depends_on:
      - "$services"
      - "$models"
      - "External\Library\SomeClass"

  - name: "$baseControllers"
    includes:
      - "App\Controller\Base\*BaseController"

  - name: "$services"
    final: false
    implements:
      - "App\Service\ServiceInterface"
    includes:
      - "App\Service\*Service"
      - "$models"
    depends_on:
      - "$models"

  - name: "$models"
    includes:
      - "App\Model\*Model"
```

### Group Properties

Each group in your `architecture.yaml` configuration can have several properties. Only `name` and `includes` are required; all other properties are optional and trigger specific architectural rules:

- **name** (required):
  - Unique identifier for the group. Prefixing with `$` is recommended to avoid confusion with class names.
  - Example: `name: "$controllers"`

- **includes** (required):
  - List of patterns or group names that define which classes/interfaces belong to this group.
  - Example: `includes: ["App\\Controller\\*Controller"]`
  - **Rule triggered:** Classes matching these patterns are considered part of the group.

- **depends_on** (optional):
  - List of group names or patterns that this group is allowed to depend on.
  - Example: `depends_on: ["$services", "App\\Library\\*"]`
  - **Rule triggered:** Ensures that classes in this group only depend on allowed groups/classes. Violations are reported if dependencies are outside this list.
  - **Important:** If a group includes from a global namespace other than `App\`, it must NOT have a `depends_on` property. This will cause a configuration error.

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
| name                            | Yes      | string    | Unique group name (recommended: `$` prefix)                                 | Defines group                                                       |
| includes                        | Yes      | array     | Patterns or group names for group membership                                | Group membership                                                    |
| depends_on                      | No       | array     | Allowed dependencies (snake_case, not camelCase)                            | Dependency restriction                                              |
| final                           | No       | boolean   | Require classes to be `final`                                               | Final class enforcement                                             |
| extends                         | No       | string    | Required base class/group                                                   | Inheritance enforcement                                             |
| implements                      | No       | array     | Required interfaces                                                         | Interface implementation enforcement                                |
| must_only_have_one_public_method_named | No | string    | Restrict to one public method with this name                                | Public method restriction                                           |

**Note:**
- Property names in YAML must use `snake_case` (e.g., `depends_on`), not camelCase.
- If a group includes from a global namespace other than `App\`, do not define `depends_on` for that group.
- The configuration will fail with a clear error if these rules are violated.

## Advanced Features

### Variable Referencing

- Groups are referenced by their name.
- The `$` prefix is recommended but not required.
- The reference must match the group name exactly.

### Pattern Matching

- Use backslashes for namespaces and `*` as a wildcard.
- Internally, `*` is converted to `.+` for regex matching.
- Example: `App\Controller\*Controller` becomes `/App\\Controller\\.+Controller/`.

### Avoiding Accidental Matches

```yaml
architecture:
  - name: "$repositories"
    final: true
    implements:
      - "App\Repository\RepositoryInterface"
    must_only_have_one_public_method_named: "find"
    includes:
      - "App\Repository\*Repository"
    depends_on:
      - "$models"
      - "App\Model\*Model"

  - name: "$models"
    includes:
      - "App\Model\*Model"
```

## Troubleshooting & FAQ

- Ensure `architecture.yaml` is in your project root.
- Check for typos in group names and references.
- For more help, see [PHPAT issues](https://github.com/carlosas/phpat/issues).

## Contributing

Contributions are welcome! Please submit issues or pull requests via GitHub.

## License

See [LICENSE](../LICENSE).

## Further Resources

- [PHPAT Documentation](https://github.com/carlosas/phpat)
- [Architecture Sniffer (Spryker)](https://github.com/spryker/architecture-sniffer)
