# `bin/code-tools` usage

- This script allows you to copy configuration files of each, or all, tools to your project so you can customize them.

## Usage

### Specific tool
- To copy the configuration file of a specific tool to your project, run the following command:
```bash
vendor/bin/code-tools publish:config <tool-name>
```

<details>
  <summary>Publish config for code-sniffer</summary>

  ```bash
  vendor/bin/code-tools publish:config code-sniffer
  ```
</details>

<details>
  <summary>Publish config for rector</summary>

  ```bash
  vendor/bin/code-tools publish:config rector
  ```
</details>

<details>
  <summary>Publish config for code-generator</summary>

  ```bash
  vendor/bin/code-tools publish:config code-generator
  ```
</details>

### All tools
- To copy the configuration files of all tools to your project, run the following command:
```bash
vendor/bin/code-tools publish:config
```

### Help
- To see the available tools, run the following command:
```bash
vendor/bin/code-tools --help
```

## Available Tools

### PHP_CodeSniffer
- A tool to detect violations of a defined coding standard.
- [Documentation](/docs/CodeSniffer/README.md)

### Rector
- A tool for automated refactoring of PHP code.
- [Documentation](/docs/Rector/README.md)

### Code Generator
- A tool to generate boilerplate code based on OpenAPI specifications.
- [Documentation](/docs/CodeGenerator/README.md)
