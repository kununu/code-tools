# .editorconfig

This repository includes a `.editorconfig` file to help maintain consistent coding styles across different editors and IDEs. EditorConfig is supported by many popular code editors and plugins, and ensures uniform formatting rules for all contributors to this project.

Learn more about `.editorconfig` at official page [here](https://editorconfig.org/).

## 📄 What is .editorconfig?

The `.editorconfig` file defines basic coding style rules (like indentation, line endings, character encoding, etc.) that are automatically applied by supported text editors.

This helps:
- Prevent accidental style changes in commits
- Maintain clean and readable code
- Standardize formatting across different team members' environments

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
