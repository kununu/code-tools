<p align="center">
  <img src="/docs/code-tools-logo.png" alt="Brancher"/>
</p>

# code-tools

- This repository contains code tools you can use in your project.
- It is a collection of tools and scripts that help us to maintain our codebase.

## Tools
### `.editorconfig`
- EditorConfig helps maintain consistent coding styles for multiple developers working on the same project across various editors and IDEs. The EditorConfig project consists of a file format for defining coding styles and a collection of text editor plugins that enable editors to read the file format and adhere to defined styles. EditorConfig files are easily readable and they work nicely with version control systems.
- Learn more about `.editorconfig` at official page [here](https://editorconfig.org/).

### `PHP-CS-Fixer`
- This project uses **PHP-CS-Fixer** to automatically format and fix PHP code according to defined coding standards. It helps maintain clean, consistent, and readable code across the codebase.

### `PHP_CodeSniffer`
- PHP_CodeSniffer is a set of two PHP scripts; the main `phpcs` script that tokenizes PHP, JavaScript and CSS files to detect violations of a defined coding standard, and a second `phpcbf` script to automatically correct coding standard violations. PHP_CodeSniffer is an essential development tool that ensures your code remains clean and consistent.
- Though the usage of this tool is not mandatory, it is highly recommended to use it to ensure the quality of the codebase.
- Learn more about PHP_CodeSniffer at official page [here](https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki).

### `Rector`
- Rector is a tool that automatically upgrades and refactors your PHP code. It is a tool that helps you to keep your code up-to-date and clean.
- Learn more about Rector at official page [here](https://getrector.com/documentation).

### `bin/code-tools`
- Though each tool can be used "out-of-the-box", this is a helper script that allows you to copy configuration files of each, or all, tools to your project so you can customize them.

### `bin/php-in-k8s`
- This is a helper script that allows you to run PHP commands inside a local Kubernetes pod without having to connect to it via a terminal manually.

### `Architecture Sniffer & PHPAT`
- **Architecture Sniffer** enforces architectural and dependency rules in your PHP codebase, helping you maintain a clean and consistent architecture.
- It is powered by [PHPAT](https://github.com/carlosas/phpat), a static analysis tool for PHP architecture testing.
- Architecture Sniffer uses a YAML configuration file (`architecture.yaml`) where you define your architectural groups and their allowed dependencies. Each group is a key under the `architecture` root, e.g.:

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
- To use Architecture Sniffer with PHPStan, add the extension to your `phpstan.neon`:
  ```neon
  includes:
      - vendor/carlosas/phpat/extension.neon
  services:
      -
          class: PHPAT\PHPStan\PHPStanExtension
          tags: [phpstan.extension]
  ```
- For more details and advanced configuration, see [Kununu/ArchitectureSniffer/README.md](docs/ArchitectureSniffer/README.md).

### Require Library as a dev dependency

You can use this library by issuing the following command:

```console
composer require --dev kununu/code-tools --no-plugins
```
- The `--no-plugins` is used to avoid the composer plugins to be executed and prevent generating unwanted configuration files, specially in projects with `symfony/flex` installed.

## Usage
- [.editorconfig](docs/EditorConfig/README.md) instructions.
- [PHP-CS-Fixer](docs/CsFixer/README.md) instructions.
- [PHP_CodeSniffer](docs/CodeSniffer/README.md) instructions.
- [Rector](docs/Rector/README.md) instructions.
- [bin/code-tools](docs/CodeTools/README.md) instructions.
- [bin/php-in-k8s](docs/PhpInK8s/README.md) instructions.
- [Architecture Sniffer & PHPAT](docs/ArchitectureSniffer/README.md) instructions.
