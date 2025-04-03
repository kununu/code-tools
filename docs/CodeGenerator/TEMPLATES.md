# Code Generator Templates Guide

This guide explains how to create and use custom templates with the Code Generator tool.

## Overview

The Code Generator uses Twig templates to generate boilerplate code for APIs. The default templates are built into the tool, but you can create custom templates to tailor the generated code to your project's specific needs.

## Template Locations

Templates can be stored in two locations:

1. **Default Templates**: Built into the code generator and used as fallbacks
2. **Custom Templates**: Located in a directory you specify using the `--template-dir` option or in the configuration file

## Template Format

Templates use the Twig templating engine and have access to various variables provided by the code generator:

```twig
<?php

declare(strict_types=1);

namespace {{ namespace }}\UseCase\Query\{{ operationName }};

/**
 * Handler for {{ operationName }} query
 */
final class QueryHandler
{
    public function __construct()
    {
        // Your constructor implementation
    }
    
    public function handle(Query $query)
    {
        // Implementation
    }
}
```

## Template Structure

The default template structure is organized by component type:

```
Templates/
├── command/            # Command-related templates
│   ├── command.php.twig
│   ├── dto.php.twig
│   ├── handler.php.twig
│   └── readme.md.twig
├── controller.php.twig # Main controller template
├── dto/                # Data transfer object templates
│   └── request.php.twig
├── misc/               # Miscellaneous configuration templates
│   └── services.yaml.twig
├── query/              # Query-related templates
│   ├── criteria.php.twig
│   ├── exception.php.twig
│   ├── handler.php.twig
│   ├── jms_serializer.yaml.twig
│   ├── query.php.twig
│   ├── read_model.php.twig
│   ├── readme.md.twig
│   └── serializer.xml.twig
├── repository/         # Repository templates
│   ├── implementation.php.twig
│   └── interface.php.twig
├── request/            # Request handling templates
│   ├── request_data.php.twig
│   └── resolver.php.twig
├── shared/             # Shared components between different types
│   └── infrastructure_query.php.twig
└── tests/              # Test templates
    ├── functional_test.php.twig
    └── unit_test.php.twig
```

## Available Variables

The following variables are available in all templates:

### Basic Variables
- `namespace`: The base namespace (e.g., `App`)
- `basePath`: The base path for generated files (e.g., `src`)
- `operation_id`: The operation ID from OpenAPI or manual input
- `method`: The HTTP method (GET, POST, PUT, DELETE)
- `path`: The URL path (e.g., `/users/{userId}`)
- `summary`: Operation summary from OpenAPI
- `description`: Operation description from OpenAPI

### Dynamic Variables
- `full_namespace`: Complete namespace for the current file
- `classname`: Class name for the current file
- `fqcn`: Fully qualified class name
- `filename`: File name without path
- `dirname`: Directory name for the current file
- `entityName` or `entity_name`: Derived entity name from the operation
- `operationName`: Properly capitalized operation name
- `cqrsType`: "Query" for GET methods or "Command" for other methods

### Request/Response Information
- `parameters`: Array of parameters from the OpenAPI specification
- `request_body`: Request body information if applicable
- `responses`: Response information
- `tags`: OpenAPI tags for the operation

### Special Variables
- `templates`: Access to all other templates being generated (see Cross-Template References)

## Cross-Template References

A powerful feature introduced is the ability for templates to reference each other. All templates have access to a `templates` variable that provides information about other files being generated.

### Usage Example

```twig
{% if templates.hasTemplate('read-model') %}
{# Check if we have a read model template #}
use {{ templates.getTemplateByType('read-model').fqcn }};
{% endif %}
```

### Available Methods

- `templates.hasTemplate(type)`: Check if a template of the given type is being generated
- `templates.getTemplateByType(type)`: Get the TemplateDTO for a specific template type
- `templates.getAllTemplates()`: Get all templates being generated
- `templates.getTemplateTypes()`: Get all template types being generated

Each template DTO provides access to:
- `type`: The template type (e.g., "controller", "query", etc.)
- `template`: The template path
- `outputPath`: Where the file will be generated
- `namespace`: Namespace for this file
- `classname`: Class name for this file
- `fqcn`: Fully qualified class name
- `path`: Original template path
- `templateVariables`: All variables available to this template

## Creating Custom Templates

To create custom templates:

1. Create a directory structure that matches the default templates
2. Copy and modify only the templates you want to customize
3. Run the generator with the `--template-dir` option pointing to your custom templates directory

### Example Directory Structure

```
my-templates/
├── controller.php.twig         # Custom controller template
├── query/
│   └── handler.php.twig        # Custom query handler template
└── tests/
    └── functional_test.php.twig # Custom functional test template
```

## Using Custom Templates

To use custom templates, you can:

1. **Command Line**: Use the `--template-dir` option
   ```bash
   vendor/bin/code-generator --template-dir=my-templates
   ```

2. **Configuration File**: Add the template path to your `.code-generator.yaml` file
   ```yaml
   templates:
     path: "path/to/my-templates"
   ```

The generator will first look for a template in your custom directory, and if it doesn't exist, fall back to the default template. This allows you to override only the templates you need while still using the defaults for the rest.

## Template Techniques

### Working with HTTP Methods

You can conditionally include code based on the HTTP method:

```twig
{% if method == 'GET' %}
    // GET-specific logic
{% elseif method == 'POST' %}
    // POST-specific logic
{% endif %}
```

### Working with Parameters

You can iterate over parameters to generate code for each:

```twig
{% if parameters is defined and parameters|length > 0 %}
    /**
     * @param array $params The request parameters
     */
    public function setParameters(array $params): void
    {
        {% for param in parameters %}
        $this->{{ param.name }} = $params['{{ param.name }}'] ?? null;
        {% endfor %}
    }
{% endif %}
```

### Using CQRS Type

The `cqrsType` variable is automatically set based on the HTTP method:

```twig
{% if cqrsType == 'Query' %}
    // Query logic for GET operations
{% else %}
    // Command logic for POST/PUT/DELETE operations
{% endif %}
```

### Advanced: Custom Path Generation

The generator automatically creates paths for files based on template patterns. You can use the following placeholders in your output patterns:

- `{basePath}`: The base path (e.g., "src")
- `{operationName}`: The operation name in PascalCase
- `{entityName}`: The entity name derived from the operation
- `{cqrsType}`: "Query" or "Command" based on HTTP method

Example:
```yaml
path_patterns:
  controller: '{basePath}/Http/Controllers/{entityName}Controller.php'
  query: '{basePath}/Domain/{entityName}/Queries/{operationName}Query.php'
```

## Working with DTOs

The Code Generator now uses a Data Transfer Object (DTO) system internally, and these DTOs are available in templates.

Three main DTO types are used:

1. **BoilerplateConfiguration**: Contains overall configuration for generation
2. **TemplateDTO**: Represents a single template/file to be generated
3. **TemplatesDTO**: Contains references to all templates being generated

These DTOs allow templates to access structured information about the generation process, which is especially useful in cross-template references.

## Debugging Templates

If you need to debug your templates, you can use Twig's `dump()` function to output variable values:

```twig
{# Debug the full namespace #}
{# {{ dump(full_namespace) }} #}

{# Debug all available templates #}
{# {{ dump(templates.getTemplateTypes()) }} #}
```

Remember to remove or comment out these debug statements in your final templates.

## Best Practices

1. **Start with Default Templates**: Begin by copying the default templates and then modify only what you need.

2. **Maintain Common Structure**: Keep the same general structure and naming conventions to ensure compatibility.

3. **Use Type Variables**: Use variables like `cqrsType` to create adaptive templates that work for both queries and commands.

4. **Leverage Cross-Template References**: Use the `templates` variable to create cohesive code that references other generated components.

5. **Reuse Code**: Extract common patterns into separate template files and include them where needed using Twig's `include` directive.

6. **Document Your Templates**: Add comments to explain the purpose and usage of your custom templates, especially for complex logic.

7. **Test Thoroughly**: Always test your custom templates with various operations to ensure they generate correct code in all scenarios. 