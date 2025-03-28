# `Code Generator` Usage

## Table of Contents
- [Overview](#overview)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Command Options](#command-options)
- [Configuration](#configuration)
  - [Default Configuration](#default-configuration)
  - [Custom Configuration](#custom-configuration)
- [File Handling](#file-handling)
- [Examples](#examples)
- [Tips and Best Practices](#tips-and-best-practices)

## Overview
The Code Generator is a tool designed to generate boilerplate code based on OpenAPI specifications. It automates the creation of controllers, repositories, commands, queries, and other components following best practices and design patterns.

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
| `--openapi-file` | `-o` | Path to OpenAPI specification file (YAML or JSON) | `tests/_data/OpenApi/openapi.yaml` |
| `--operation-id` | `-i` | Operation ID from OpenAPI specification to use for generation | - |
| `--config` | `-c` | Path to configuration file | `.code-generator.yaml` |
| `--non-interactive` | - | Run in non-interactive mode (requires all options to be provided) | `false` |
| `--force` | `-f` | Force overwrite existing files without confirmation | `false` |
| `--skip-existing` | `-s` | Skip all existing files without confirmation | `false` |
| `--quiet` | `-q` | Do not output any messages | `false` |

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
# Base namespace for generated code
namespace: 'App'

# Default OpenAPI specification file path
default_openapi_path: 'tests/_data/OpenApi/openapi.yaml'

# Whether to skip existing files without confirmation
skip_existing: false

# Whether to force overwrite existing files without confirmation
force: false

# Path patterns for all available templates
path_patterns:
  # Controller
  controller: '{basePath}/Controller/{operationName}Controller.php'
  
  # CQRS Query related templates
  query: '{basePath}/UseCase/Query/{operationName}/Query.php'
  # ... other path patterns ...

# Enable/disable specific generators
generators:
  controller: true
  dto: true
  command: true
  repository: true
  xml-serializer: true
```

## File Handling
The Code Generator provides several options for handling existing files:

1. **Interactive Mode (Default)**: If a file already exists, you'll be prompted to confirm whether to overwrite it.
2. **Force Mode (`--force`)**: All existing files will be overwritten without confirmation.
3. **Skip Mode (`--skip-existing`)**: All existing files will be skipped without confirmation.

## Examples

### Generate code for a specific OpenAPI operation
```bash
vendor/bin/code-generator --openapi-file=api/openapi.yaml --operation-id=getUserById
```

### Generate code with a custom configuration file
```bash
vendor/bin/code-generator --config=my-config.yaml
```

### Generate code and force overwrite existing files
```bash
vendor/bin/code-generator --force
```

### Generate code and skip all existing files
```bash
vendor/bin/code-generator --skip-existing
```

### Non-interactive mode with all required options
```bash
vendor/bin/code-generator --non-interactive --openapi-file=api/openapi.yaml --operation-id=getUserById
```

## Tips and Best Practices
- Always review generated code to ensure it meets your requirements.
- Consider customizing the path patterns to match your project structure.
- Enable only the generators you need to avoid creating unnecessary files.
- Use the `--skip-existing` option when you want to preserve manually modified files.
