<p align="center">
  <img src="/docs/code-tools-logo.png" alt="kununu Code Tools"/>
</p>

# kununu Code Tools

Shared development tooling for kununu PHP projects. It centralizes the static-analysis and code-style configuration our repositories use, and ships two helper executables.

## Installation

```console
composer require --dev kununu/code-tools --no-plugins
```

`--no-plugins` keeps composer plugins from running during installation, which would otherwise generate unwanted configuration files, particularly in projects using `symfony/flex`.

## Tools

Every tool works out of the box. Use [`bin/code-tools`](docs/CodeTools/README.md) to publish a tool's configuration template into your project when you need to customise it.

| Tool                                                       | Purpose                                                                      |
|------------------------------------------------------------|------------------------------------------------------------------------------|
| [`.editorconfig`](docs/EditorConfig/README.md)             | Consistent editor and IDE settings across the team                           |
| [PHP-CS-Fixer](docs/PHPCSFixer/README.md)                  | Formats and fixes code against the kununu coding standard                    |
| [PHP_CodeSniffer](docs/PHPCodeSniffer/README.md)           | Reports and fixes coding-standard violations, including custom kununu sniffs |
| [PHPStan](docs/PHPStan/README.md)                          | Static analysis                                                              |
| [Psalm](docs/Psalm/README.md)                              | Static analysis with type inference                                          |
| [Rector](docs/Rector/README.md)                            | Automated refactoring and PHP version upgrades                               |
| [Architecture Sniffer](docs/ArchitectureSniffer/README.md) | Architecture and dependency rules, powered by PHPAT                          |
| [`bin/code-tools`](docs/CodeTools/README.md)               | Publishes the configuration templates above into your project                |
| [`bin/php-in-k8s`](docs/PHPInK8s/README.md)                | Runs PHP commands inside a local Kubernetes pod                              |

## Upgrading

Coming from 4.x? See [UPGRADE.md](UPGRADE.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, testing, and the PR/release process.

------------------------------

![Continuous Integration](https://github.com/kununu/code-tools/actions/workflows/continuous-integration.yml/badge.svg)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=kununu_code-tools&metric=alert_status)](https://sonarcloud.io/dashboard?id=kununu_code-tools)
