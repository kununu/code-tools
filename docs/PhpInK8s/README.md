# `php-in-k8s` usage
This is a helper script that allows you to run PHP commands inside a local Kubernetes pod without connecting to it via a terminal manually.
It automatically resolves the appropriate pod, maps host paths within a project to container paths in input arguments, and maps the paths back to the host in the output.
There is a way to let PHPStorm [use this script as a local PHP interpreter](https://new-work.atlassian.net/wiki/x/M4EjV) and [simplify work with PHPUnit tests](https://new-work.atlassian.net/wiki/x/AQCPR) using such an interpreter.

## Usage

### Running a PHP script
```bash
vendor/bin/php-in-k8s bin/console
```

### Running PHP without script
```bash
vendor/bin/php-in-k8s -r "echo 'Hello, World!';"
```
  

