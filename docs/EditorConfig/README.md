# .editorconfig

This repository includes a `.editorconfig` file to help maintain consistent coding styles across different editors and IDEs. EditorConfig is supported by many popular code editors and plugins, and ensures uniform formatting rules for all contributors to this project.

Learn more about `.editorconfig` at official page [here](https://editorconfig.org/).

## 📄 What is .editorconfig?

The `.editorconfig` file defines basic coding style rules (like indentation, line endings, character encoding, etc.) that are automatically applied by supported text editors.

This helps:
- Prevent accidental style changes in commits
- Maintain clean and readable code
- Standardize formatting across different team members' environments

## 🎛️ Usage

To enable it just copy the `.editorconfig` file to your repository root (or use the `vendor/bin/code-tools publish:config editorconfig` command).

For PHPStorm you can find the instruction [here](https://www.jetbrains.com/help/phpstorm/editorconfig.html).

Alternatively you can go to `Settings -> Editor -> Code Style -> PHP -> Scheme -> ⚙️ -> Import Scheme...` and import the `.editorconfig` file as inspection settings without the need to add the file to the repository.

## ⚙️ Sample .editorconfig

```ini
root = true

[*]
charset = utf-8
indent_style = space
indent_size = 4
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false
