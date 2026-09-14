# Contributing

- Do you have a user story that requires changes in this library?
- Are you fixing a bug?
- You want to update some component/library, and you are not *dependabot*?
- You want to add an awesome feature or do a refactor on some part?

If the answer was **YES** to any of the questions then you can contribute by creating a Pull Request on [GitHub](https://github.com/kununu/code-tools).

## Development setup

### Requirements

- The minimum PHP version and the required extensions are declared in `composer.json` (`require.php` and the `ext-*` entries).

### Setup

Install dependencies:

```console
composer install
```

### Coding standards and static analysis

- We use a slightly modified version of the [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/) and so should you on your Pull Requests.
- We use the following linters/code standard tools:
    - PHP CS Fixer
    - PHP_CodeSniffer
- We have configured some static analysis tools:
    - Rector
    - PHPStan
    - Psalm

#### Scripts

```console
composer ci             # Run same checks as in CI (note: applies CS fixes, CI only checks)
composer cs             # PHP CS Fixer (kununu code standards) (dry-run)
composer cs-fix         # PHP CS Fixer (kununu code standards) (apply-fixes)
composer sniffer        # PHP_CodeSniffer (dry-run)
composer sniffer-fix    # PHP_CodeSniffer (apply fixes)
composer rector         # Rector (dry-run)
composer rector-fix     # Rector (apply fixes)
composer phpstan        # PHPStan static analysis
composer psalm          # Psalm static analysis
composer test           # Run the full PHPUnit test suite
composer test-coverage  # Run the full PHPUnit test suite with a coverage report
```

All scripts are defined in `composer.json` under `scripts`.

The same tools run in CI (`.github/workflows/continuous-integration.yml`), which also checks `composer-audit`, `composer-normalize`, `composer-require-checker`, and `composer-dependency-analyser`.

`composer-dependency-analyser`, `composer-require-checker` and `composer-normalize` are global tools, installed in CI via `setup-php`. Install them locally to run `composer ci` in full.

`composer ci` runs the same set of tools as in CI:
- Composer Audit
- Composer Normalizer
- Composer Require Checker
- Composer Dependency Analyser
- PHP CS Fixer
- PHP_CodeSniffer
- Rector
- PHPStan
- Psalm
- PHPUnit tests

#### Shell scripts

The executables in `bin/` must keep working on **bash 3.2**, which is what macOS still ships. Modern
bash accepts constructs 3.2 rejects, and vice versa, so a script that runs on Linux can still break
for a colleague on a Mac.

Nothing in `composer ci` covers this, so check it with docker or podman (or similar tools):

```console
podman run --rm -v "$PWD:/repo:ro" -w /repo docker.io/library/bash:3.2 \
  bash -c 'for f in bin/*; do echo "$f"; bash -n "$f" || exit 1; done'
```

Swap `podman` for `docker` if that is what you have. Note that `bash -n` only accepts one script, so
the loop is what makes it cover every file. It also only parses: to exercise a script's behaviour,
mount the repo and run it inside the same container.

### Code Guidelines

- Follow our [coding guidelines](CODING-GUIDELINES.md). If you have any doubts let us know.

### Tests

- We want to ensure that our code is stable and has high quality standards.
    - We use unit tests.
    - 💡 We must keep our coverage at or above **95%**. Make sure to run and generate a coverage report and check if your changes keep the global coverage at or above that level.
        - So add coverage to your new code, fix any broken tests resulting from your code and add coverage if your code change something.
    - 💡 For the "fresh" code (i.e. code added or changed in your pull request) coverage should be at or above **90%**.
    - This will be enforced by a SonarQube Cloud check by the CI (`.github/workflows/continuous-integration.yml`)

Run the test suite:

```console
composer test
```

Run the test suite with a coverage report:

```console
composer test-coverage
```
### Documentation

Each feature under `src/` has a corresponding per-tool deep Markdown page document in `docs/`, one folder per tool, indexed from `README.md`.
When adding or changing a feature, update its `docs/` page and the index.

## Pull Requests

Take into consideration the following guidelines:

### Branches
- **main** is the stable branch, create new feature branches for your changes:
    - If implementing a user story name your branches according to your user story. E.g. ***KUNSOMETHING-9999***.
    - If your user story requires multiple pull requests then add some meaning to the branch. E.g.: ***KUNSOMETHING-9999-first-feature***, ***KUNSOMETHING-9999-second-feature***.
    - If not on the context of a user story, then create a meaningful branch name. E.g.: ***implement-my-feature***.
        - Do not cluster all the words into a single one
            - **DO**: `implement-my-feature`
            - **DON'T DO**: `implementmyfeature`
    - Keep the case of the branches like in the examples (***KUNSOMETHING-9999***, ***KUNSOMETHING-9999-my-feature***, ***implement-my-feature***).
        - So, besides the (potential) JIRA issue prefix (KUNSOMETHING-9999), use **kebab case** (lowercase words separated by hyphens). Underscores are **not** allowed
    - Branch names are validated in CI against this pattern: `^((KUN[A-Z][A-Z0-9]*-[0-9]+(-[a-z0-9]+)*)|([a-z]{2}[-a-z0-9]*[a-z0-9]))$`

### Pull Requests Titles
- Like the branches you need to give good title to the PR:
    - If implementing a user story. E.g.: ***KUNSOMETHING-9999 Name of the story on JIRA***.
        - This format is **mandatory** for PRs based on user stories.
        - If multiple PRs for a user story then use the same pattern as depicted in the branches sections. Examples:
            - ***KUNSOMETHING-9999 Name of the story on JIRA - Part 1***
            - ***KUNSOMETHING-9999 Name of the story on JIRA - Migrations***
            - ***KUNSOMETHING-9999 Name of the story on JIRA - Queue readers***
            - etc.
    - If not, a meaningful title. E.g. ***Implementation of my feature***.

### Pull Requests Descriptions
- It's always nice to have context regarding the pull request:
    - If your PR is related to a user story then is **mandatory to add a link to JIRA**.
    - If your PR is related to a GitHub issue then is **mandatory to add a link to that issue**.
    - Is **mandatory to add a description** of what is the purpose of the PR and/or the changes being done on it.
        - Ideally add some technical details.
    - Keep it readable: prefer short, distinct paragraphs over one long block of text.
    - When listing the changes, lead each item with the area/file it affects and nest the explanation beneath it, instead of burying it in one long inline sentence.
    - Do not add AI-tool "generated by" / attribution footers to the PR body (e.g. "Generated with ..."). Tool or co-author attribution belongs in the commit (e.g. a `Co-Authored-By` trailer), not the description.

### Pull Requests Template

Every PR that you open will be pre-filled with the [template](./.github/PULL_REQUEST_TEMPLATE.md) to help you follow these guidelines. Make sure to fill in the blanks/change and **remove the comments** (the lines with `<!-- -->`).

### Pull Requests Status
- Opening a Pull Request:
    - On opening the PR it will be auto-assigned to you.
    - If you are still working on you PR then create your PR as a **Draft Pull Request**.
        - The reason is that if you don't the code owners of the repository will receive emails notifications and your PR is not ready yet.
    - **Coding agents must always open the PR as a draft** (`gh pr create --draft`) and must not mark it ready for review or request reviewers until the person who requested the work confirms it is ready.
    - Optionally, add the **"work in progress"** label.
- Additional labels:
    - If your PR is not meant to be merged right away then you use the label **"do not merge"**.
- Code review of the Pull Request:
    - Ensure all quality gates pass before requesting review
    - All PRs will assign the `CODEOWNERS` (`@kununu/backend-libraries`) team members as code reviewers.
    - You can also add additional people for code review.
    - If your PR is a draft then convert it to a regular PR.
    - Remove the label **"work in progress"** if you have added it before.
- Merging PRs:
    - The repository is configured to delete the branches on this action.

### Commit messages
- Write good commit messages!
- *"Small improvement"*, *"Fix"*, *"Code improvement"* are **NOT** good messages (you improved what? you fixed what?).
- Try to use something like **[IMPERATIVE VERB]** **[SUBJECT]**. E.g.: "Introduce new queue reader", "Fix typo in class XPTO", etc.
- A good read [here](https://chris.beams.io/posts/git-commit/).

## Releasing

The package is distributed via Composer and versioned with Git tags following SemVer. Because most kununu PHP repos depend on this package, and `dist/*.dist` templates and the `code-tools` binary form its public API, treat any change to them as breaking unless proven otherwise.
