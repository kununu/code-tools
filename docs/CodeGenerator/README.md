# `Code Generator` Usage

## Table of Contents
- [Overview](#overview)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Command Options](#command-options)
- [Configuration](#configuration)
  - [Default Configuration](#default-configuration)
  - [Custom Configuration](#custom-configuration)
  - [Custom Templates](#custom-templates)
- [Manual Data Entry](#manual-data-entry)
- [File Handling](#file-handling)
- [Cross-Template References](#cross-template-references)
- [Examples](#examples)
- [Tips and Best Practices](#tips-and-best-practices)

## Overview
The Code Generator is a tool designed to generate boilerplate code based on OpenAPI specifications or manually provided operation details. It automates the creation of controllers, repositories, commands, queries, DTOs, and other components following best practices and design patterns.

The tool now supports enhanced template functionality, cross-template references, and more flexible output path generation, making it even more powerful for quickly scaffolding new API endpoints in your application.

## Installation
The Code Generator is included in the `kununu/code-tools` package. To use it, you need to have this package installed in your project:

```bash
composer require --dev kununu/code-tools
```

## Basic Usage
To generate boilerplate code, run the following command:

```bash
vendor/bin/code-generator
```

By default, this will use the configuration from `.code-generator.yaml` in your project root and prompt you for any required inputs.

## Command Options
The Code Generator supports the following options:

| Option | Short | Description | Default |
|--------|-------|-------------|---------|
| `--config` | `-c` | Path to configuration file | `.code-generator.yaml` |
| `--operation-id` | `-i` | Operation ID from OpenAPI specification | - |
| `--openapi-file` | `-o` | Path to OpenAPI specification file | - |
| `--manual` | `-m` | Manually provide operation details instead of using OpenAPI | `false` |
| `--force` | `-f` | Force overwrite of existing files without confirmation | `false` |
| `--skip-existing` | `-s` | Skip all existing files without confirmation | `false` |
| `--non-interactive` | - | Run in non-interactive mode | `false` |
| `--template-dir` | `-t` | Path to custom template directory | - |
| `--quiet` | `-q` | Suppress all output except errors | `false` |
| `--no-color` | - | Disable colored output | `false` |

## Configuration
The Code Generator can be configured using a YAML configuration file. By default, it looks for `.code-generator.yaml` in your project root.

### Default Configuration
To create a default configuration file in your project, run:

```bash
vendor/bin/code-tools publish:config code-generator
```

This will copy the default configuration file to your project root as `.code-generator.yaml`.

### Custom Configuration
You can customize the configuration file to suit your needs. The following options are available:

```yaml
# Base path for generated code
base_path: 'src'

# Base namespace for generated code
namespace: 'App'

# Default OpenAPI specification file path
default_openapi_path: 'path/to/openapi.yaml'

# Force overwrite existing files
force: false

# Skip existing files
skip_existing: false

# Custom templates directory
templates:
  path: 'dist/templates'

# Custom path for generated files
path_patterns:
  controller: '{basePath}/Controller/{operationName}Controller.php'
  query: '{basePath}/UseCase/Query/{operationName}/Query.php'
  # ... other path patterns

# Enable/disable specific generators
generators:
  controller: true # Generates the Controller file
  use-case: true # Generates all files under UseCase namespace
  cqrs-command-query: true # Generates basic CQRS command and query files, such as Command and CommandHandler.
  read-model: true # Generates read model related files.
  request-mapper: true # Generates request data mapping files, such as RequestData and RequestResolver.
  repository: true # Generates repository files, such as RepositoryInterface, its implementation, and the Query used by the repository.
  xml-serializer: true # Generates XML serializer configuration files.
  tests: true # Generates test files, such as unit and functional tests.
```

### Custom Templates
The Code Generator supports using custom templates instead of the built-in ones. You can specify a custom template directory in two ways:

1. **Via Configuration File**:
   ```yaml
   templates:
     path: 'dist/templates'
   ```

2. **Via Command Line**:
   ```bash
   vendor/bin/code-generator --template-dir=dist/templates
   ```

When using custom templates, the Code Generator will:

1. Check if each required template exists in your custom directory
2. Use the custom version if available
3. Fall back to the built-in template if not found
4. Show you which templates are being used from which source during generation

#### Template Structure
Your custom templates should follow the same structure as the built-in templates. The main template directories are:

- `command/` - Templates for command-related files
- `query/` - Templates for query-related files
- `repository/` - Templates for repository files
- `request/` - Templates for request-related files
- `tests/` - Templates for test files
- `shared/` - Templates shared between different types
- `dto/` - Templates for data transfer objects
- `misc/` - Miscellaneous templates like configuration files

See the [TEMPLATES.md](TEMPLATES.md) file for more details on how to create and use custom templates.

## Manual Data Entry
If you don't have an OpenAPI specification or prefer to provide operation details manually, you can use the `--manual` option:

```bash
vendor/bin/code-generator --manual
```

The tool will prompt you for all necessary information, including:
- Operation ID
- HTTP method
- Path
- Request parameters
- Response structure

## File Handling
The Code Generator provides options for handling existing files:

- **Preview**: By default, the tool shows a preview of files to be generated, including whether they exist and will be overwritten or skipped
- **Force Overwrite**: Use `--force` to overwrite all existing files without confirmation
- **Skip Existing**: Use `--skip-existing` to skip all existing files without confirmation

## Cross-Template References
A powerful feature of the Code Generator is the ability for templates to reference each other. This is done through the `templates` variable that's available in all templates:

```twig
{# In a template file #}
{% if templates.hasTemplate('read-model') %}
  // Use data from the read model template
  $readModel = {{ templates.getTemplateByType('read-model').namespace }}\{{ templates.getTemplateByType('read-model').classname }};
{% endif %}
```

This allows you to create templates that adapt based on what other files are being generated, creating more cohesive and interconnected code.

## Examples

### Generate code from OpenAPI specification
```bash
vendor/bin/code-generator --openapi-file=api/openapi.yaml --operation-id=getUserProfile
```

### Generate code with custom configuration and templates
```bash
vendor/bin/code-generator --config=my-config.yaml --template-dir=my-templates
```

### Generate code in non-interactive mode
```bash
vendor/bin/code-generator --non-interactive --openapi-file=api/openapi.yaml --operation-id=getUserProfile --force
```

### Generate code with manual input
```bash
vendor/bin/code-generator --manual
```

## Tips and Best Practices

1. **Custom Templates**: Start by copying the built-in templates to your custom directory and then modify only what you need.

2. **Configuration File**: Create a project-specific configuration file to maintain consistent settings.

3. **Path Patterns**: Customize path patterns to match your project structure.

4. **Entity Name Convention**: The tool automatically extracts an entity name from the operation ID, but you can override this by adding a custom template variable: `entity_name`.

5. **Test Templates**: The generated tests are designed to be minimal starting points. They include `markTestSkipped()` calls that you should replace with actual test implementations.

6. **Template Variables**: When creating custom templates, you have access to all variables from the operation details, including:
   - `operation_id` - The operation ID
   - `method` - The HTTP method
   - `path` - The API path
   - `parameters` - Request parameters
   - `namespace` - The base namespace
   - `full_namespace` - The complete namespace for the file
   - `classname` - The generated class name
   - `entity_name` - Extracted entity name from the operation ID
   - `cqrsType` - Automatically set to "Query" for GET methods or "Command" for others
   - `templates` - Access to other templates being generated

7. **Cross-Template References**: Use the `templates` variable to create cohesive code across multiple files.

8. **Naming Conventions**: The generator follows naming conventions automatically:
   - Controller names are derived from the operation ID (e.g., `getUserProfile` → `GetUserProfileController`)
   - Entity names are extracted from the operation ID (e.g., `getUserProfile` → `User`)
   - Command/Query names follow the operation (e.g., `getUserProfile` → `GetUserProfileQuery`)

### Enable/Disable Specific Generators

The Code Generator allows you to enable or disable specific code generators through the `generators` section in your configuration file:

| Generator Option | Description | Controls |
|------------------|-------------|----------|
| `controller` | Generates API controller files | Controller files |
| `use-case` | Controls generation of all files under UseCase namespace | All files in UseCase directory structure |
| `cqrs-command-query` | Controls basic CQRS command and query files | Command, command-handler, query, and query-handler files |
| `read-model` | Controls read model related files | read-model, query-serializer-xml, and jms-serializer-config files |
| `request-mapper` | Controls request data mapping files | request-data and request-resolver files |
| `repository` | Controls repository implementation files | All repository interface and implementation files |
| `xml-serializer` | Controls XML serializer configuration generation | XML serializer files |
| `tests` | Controls test file generation | All unit and functional test files |

This gives you fine-grained control over which components are generated, allowing you to adapt the tool to your specific project needs.
