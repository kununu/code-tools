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
- [Examples](#examples)
- [Tips and Best Practices](#tips-and-best-practices)

## Overview
The Code Generator is a tool designed to generate boilerplate code based on OpenAPI specifications or manually provided operation details. It automates the creation of controllers, repositories, commands, queries, and other components following best practices and design patterns.

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
| `--config` | `-c` | Path to configuration file | `code-generator.yaml` |
| `--operation-id` | `-o` | Operation ID from OpenAPI specification | - |
| `--openapi` | `-a` | Path to OpenAPI specification file | - |
| `--manual` | `-m` | Manually provide operation details instead of using OpenAPI | `false` |
| `--force` | `-f` | Force overwrite of existing files without confirmation | `false` |
| `--skip-existing` | `-s` | Skip all existing files without confirmation | `false` |
| `--non-interactive` | `-n` | Run in non-interactive mode | `false` |
| `--no-preview` | `-p` | Skip preview of files to be generated | `false` |
| `--template-dir` | `-t` | Path to custom template directory | - |
| `--quiet` | `-q` | Suppress all output | `false` |
| `--no-color` | - | Disable colored output | `false` |

## Configuration
The Code Generator can be configured using a YAML configuration file. By default, it looks for `code-generator.yaml` in your project root.

### Default Configuration
To create a default configuration file in your project, run:

```bash
vendor/bin/code-tools publish:config code-generator
```

This will copy the default configuration file to your project root as `code-generator.yaml`.

### Custom Configuration
You can customize the configuration file to suit your needs. The following options are available:

```yaml
# Base path for generated code
base_path: 'src'

# Base namespace for generated code
namespace: 'App'

# Default OpenAPI specification file path
default_openapi_path: null

# Force overwrite existing files
force: false

# Skip existing files
skip_existing: false

# Custom templates directory
templates:
  path: 'dist/templates'

# Custom path patterns for generated files
path_patterns:
  controller: '{basePath}/Controller/{operationName}Controller.php'
  query: '{basePath}/UseCase/Query/{operationName}/Query.php'
  # ... other path patterns

# Enable/disable specific generators
generators:
  controller: true
  dto: true
  command: true
  repository: true
  tests: true
```

### Custom Templates
The Code Generator now supports using custom templates instead of the built-in ones. You can specify a custom template directory in two ways:

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

This allows you to customize specific templates while still using the default ones for everything else.

#### Template Structure
Your custom templates should follow the same structure as the built-in templates. The main template directories are:

- `command/` - Templates for command-related files
- `query/` - Templates for query-related files
- `repository/` - Templates for repository files
- `request/` - Templates for request-related files
- `tests/` - Templates for test files

The most commonly customized templates are:

- `controller.php.twig` - Controller template
- `tests/unit_test.php.twig` - Unit test template
- `tests/functional_test.php.twig` - Functional test template

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

## Examples

### Generate code from OpenAPI specification
```bash
vendor/bin/code-generator --openapi=api/openapi.yaml --operation-id=getUserProfile
```

### Generate code with custom configuration and templates
```bash
vendor/bin/code-generator --config=my-config.yaml --template-dir=my-templates
```

### Generate code in non-interactive mode
```bash
vendor/bin/code-generator --non-interactive --openapi=api/openapi.yaml --operation-id=getUserProfile --force
```

## Tips and Best Practices

1. **Custom Templates**: Start by copying the built-in templates to your custom directory and then modify only what you need.

2. **Configuration File**: Create a project-specific configuration file to maintain consistent settings.

3. **Path Patterns**: Customize path patterns to match your project structure.

4. **Test Templates**: The test templates are designed to be minimal and require implementation. They include `markTestSkipped()` to remind you to implement the tests.

5. **Template Variables**: When creating custom templates, you have access to all the variables from the operation details, including:
   - `operation_id` - The operation ID
   - `method` - The HTTP method
   - `path` - The API path
   - `parameters` - Request parameters
   - `namespace` - The base namespace
   - `entity_name` - Extracted entity name from the operation ID
