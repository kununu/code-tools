<p align="center">
  <img src="/docs/code-tools-logo.png" alt="Brancher"/>
</p>

# code-tools

- This repository contains code tools you can use in your project.  
- It is a collection of tools and scripts that help us to maintain our codebase.

## Tools
### `PHP_CodeSniffer`
- PHP_CodeSniffer is a set of two PHP scripts; the main `phpcs` script that tokenizes PHP, JavaScript and CSS files to detect violations of a defined coding standard, and a second `phpcbf` script to automatically correct coding standard violations. PHP_CodeSniffer is an essential development tool that ensures your code remains clean and consistent.
- Though the usage of this tool is not mandatory, it is highly recommended to use it to ensure the quality of the codebase.
- Learn more about PHP_CodeSniffer at official page [here](https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki).

### `Rector`
- Rector is a tool that automatically upgrades and refactors your PHP code. It is a tool that helps you to keep your code up-to-date and clean.
- Learn more about Rector at official page [here](https://getrector.com/documentation).

### `bin/code-tools`
- Though each tool can be used "out-of-the-box", this is a helper script that allows you to copy configuration files of each, or all, tools to your project so you can customize them.

## Install

### Add custom private repositories to composer.json

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/kununu/code-tools.git",
      "no-api": true
    }
  ]
}
```

### Require Library as a dev dependency

You can use this library by issuing the following command:

```console
composer require --dev kununu/code-tools --no-plugins
```
- The `--no-plugins` is used to avoid the composer plugins to be executed and prevent generating unwanted configuration files, specially in projects with `symfony/flex` installed.

## Usage
- [PHP_CodeSniffer](docs/CodeSniffer/README.md) instructions.
- [Rector](docs/Rector/README.md) instructions.
- [bin/code-tools](docs/CodeTools/README.md) instructions.