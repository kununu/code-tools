# Architecture tests
**Purpose:** Every domain has its own architecture. With this test you can make sure that the architecture is followed. This test will check if the architecture is followed by checking the following:
1. The folder structure is followed
2. The naming conventions are followed
3. The classes are in the right namespace
4. Layers only depend on layers they are allowed to
5. The classes are always extending the classes defined
6. The classes are always implementing the classes defined
7. The classes are strictly final
8. The classes are only having one public method with the name defined

## Get started
1. Install the dependencies
```bash
composer require --dev phpunit/phpunit phpat/phpat
```
2. Configure phpstan.neon
```neon
includes:
    - vendor/phpat/phpat/extension.neon

parameters:
    ...
    phpat:
            ignore_built_in_classes: false
            show_rule_names: true
    ...

services:
    - class: Kununu\ArchitectureTest\FollowsFolderStructureRule
      tags:
        - phpstan.rules.rule

    - class: Kununu\ArchitectureTest\ArchitectureTest
      tags:
        - phpat.test
```

3. Define your architecture rules by creating an `arch_definition.yaml` in the root of your project

### How to define your architecture
#### Requirements for the FollowsFolderStructureRule
```yaml
architecture:
    - layer: FirstLayer
    - layer: SecondLayer

deprecated:
    - layer: DeprecatedLayer
```
This will make sure no other folders are created in the root (/src).
In this example the only folders allowed are FirstLayer, SecondLayer and DeprecatedLayer.
The deprecated layer will be kept ignored, in case you are in the process of removing it.

#### Require sublayers with the namespace or class definition
```yaml
architecture:
    - layer: FirstLayer
      sublayers:
        - name: FirstLayer1
          class: "App\\FirstLayer\\ClassName"
        - name: FirstLayer2
          namespace: "App\\FirstLayer\\SubNamespace"
    - layer: SecondLayer
      sublayers:
        - name: SecondLayer1
          class: "App\\SecondLayer\\*\\ClassName"
        - name: SecondLayer2
          namespace: "App\\SecondLayer\\*\\SubNamespace"
```
You can use * to match any class or namespace.
These are used as the base, in which all classes will be checked against the rules defined.
#### Define the rules for the layers

The following rules are currently available:
- **dependency-whitelist**: This will check that the defined sublayer is only using the classes defined by the Whitelist.
- **extends**: This will check that the defined sublayer is always extending the defined class.
- **implements**: This will check that the defined sublayer is always implementing the defined class.
- **final**: This will check that all classes in defined sublayer are always final.
- **only-one-public-method-named**: This will check that the defined sublayer is only having one public method with the name defined. This is used to make sure that the class is only used as e.g. Controller, Command and etc.
```yaml
architecture:
    - layer: FirstLayer
      sub-layers: 
        - name: FirstLayer1
          class: "App\\FirstLayer\\ClassName"
          dependency-whitelist:
            - interface: "Doctrine\\ORM\\EntityManagerInterface"
            - class: "App\\Application\\*\\Command"
            - namespace: "Another\\SubNamespace"
          extends:
            class: "App\\FirstLayer\\AbstractFirstLayerClass"
          implements:
            - interface: "App\\FirstLayer\\FirstLayerInterface"
          final: true
          only-one-public-method-named: "__invoke"
```

## You are ready to go
You can test your setup by running the following command:
```bash
php services/vendor/bin/phpstan clear-result && php services/vendor/bin/phpstan analyse -c services/phpstan.neon --memory-limit 240M
```
This will clear the cache and run the tests.

You can run the tests in your directory or in the container.
