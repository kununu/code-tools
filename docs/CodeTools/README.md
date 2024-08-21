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
