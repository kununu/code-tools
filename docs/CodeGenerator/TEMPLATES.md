# Code Generator Templates Guide

This guide explains how to create and use custom templates with the Code Generator tool.

## Overview

The Code Generator uses templates to generate boilerplate code for APIs. The default templates are built-in, but you can create custom templates to tailor the generated code to your project's specific needs.

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

## Available Variables

The following variables are available in templates:

- `namespace`: The base namespace (e.g., `App`)
- `basePath`: The base path for generated files (e.g., `src`)
- `operationName`: The name of the operation from OpenAPI or manual input
- `operationMethod`: The HTTP method (GET, POST, PUT, DELETE)
- `operationPath`: The URL path (e.g., `/users/{userId}`)
- `operationParameters`: Array of parameters from the OpenAPI specification
- `requestBody`: Request body information if applicable
- `responses`: Response information
- `entityName`: Derived entity name from the operation

## Creating Custom Templates

To create custom templates:

1. Create a directory structure that matches the default templates
2. Copy and modify the templates you want to customize
3. Run the generator with the `--template-dir` option pointing to your custom templates directory

### Directory Structure

Your custom templates directory should follow this structure:

```
templates/
├── controller.twig
├── query-handler.twig
├── query.twig
└── ...
```

## Using Custom Templates

To use custom templates, you can:

1. **Command Line**: Use the `--template-dir` option
   ```bash
   bin/code-generator --template-dir=my-templates
   ```

2. **Configuration File**: Add the template path to your `.code-generator.yaml` file
   ```yaml
   templates:
     path: my-templates
   ```

## Template Overriding

The generator first looks for a template in your custom directory, and if it doesn't exist, falls back to the default template. This allows you to override only the templates you need to customize.

## Example: Customizing a Controller Template

Here's an example of customizing the controller template:

1. Create `my-templates/controller.twig`:
   ```twig
   <?php
   
   declare(strict_types=1);
   
   namespace {{ namespace }}\Controller;
   
   use Symfony\Component\HttpFoundation\JsonResponse;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\Routing\Annotation\Route;
   
   /**
    * @Route("{{ operationPath }}", methods={"{{ operationMethod }}"})
    */
   final class {{ operationName }}Controller
   {
       public function __invoke(Request $request): JsonResponse
       {
           // Custom implementation
           return new JsonResponse(['result' => 'success']);
       }
   }
   ```

2. Run the generator with your custom template directory:
   ```bash
   bin/code-generator --template-dir=my-templates
   ```

## Advanced Template Techniques

### Conditionals

You can use Twig conditionals to vary the generated code:

```twig
{% if operationMethod == 'GET' %}
    // Implement retrieval logic
{% elseif operationMethod == 'POST' %}
    // Implement creation logic
{% endif %}
```

### Loops

You can iterate over parameters, request properties, etc.:

```twig
{% if operationParameters|length > 0 %}
    /**
     * @param array $params
     */
    public function setParameters(array $params): void
    {
        {% for param in operationParameters %}
        $this->{{ param.name }} = $params['{{ param.name }}'] ?? null;
        {% endfor %}
    }
{% endif %}
```

## Troubleshooting

If you encounter issues with your templates:

1. Verify that your template directory path is correct
2. Check template syntax for errors
3. Run the generator with `-v` for verbose output to see which templates are being used
4. Verify that template names match the expected naming convention 