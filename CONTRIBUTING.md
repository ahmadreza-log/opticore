# Contributing to OptiCore

Welcome, and thank you for considering a contribution to OptiCore! This document outlines how to propose changes, request features, and report issues so we can maintain a predictable and friendly workflow.

## Code of Conduct

Please review and follow the project [Code of Conduct](CODE_OF_CONDUCT.md). By participating in this repository you agree to uphold these standards.

## How to Contribute

There are many ways to help:

- Report bugs or performance regressions.
- Suggest new optimisation toggles and configuration options.
- Improve documentation and developer experience.
- Help reproduce outstanding issues or review pull requests.

## Reporting Bugs

When filing an issue:

1. Search open issues to avoid duplicates.
2. Include the following details:
   - WordPress and PHP versions.
   - Active theme and relevant plugins.
   - Steps to reproduce and expected behaviour.
   - OptiCore settings involved (screenshots or an exported option array are helpful).
3. Attach any relevant logs, stack traces, or browser console output.

Security-related reports should **not** be submitted through the public issue tracker. See [SECURITY.md](SECURITY.md) for responsible disclosure instructions.

## Suggesting Enhancements

Use the feature request issue template and describe:

- The optimisation goal.
- Proposed implementation details or references.
- Any potential backwards compatibility or UX considerations.

## Development Workflow

1. Fork the repository and create a feature branch from `main`.
2. Ensure new PHP code follows WordPress Coding Standards.
3. Wrap user-facing strings in the appropriate localisation function (e.g., `__()`, `_e()`).
4. Keep or improve inline documentation (PHPDoc and JS comments) so the codebase remains easy to read.
5. Add or update documentation and tests where applicable.
6. Run linting or formatting tools relevant to your changes (PHP_CodeSniffer, etc.).
7. Commit using clear messages and open a pull request referencing related issues.

## Pull Requests

- Keep changes focused; smaller PRs are easier to review.
- Describe the motivation, approach, and testing performed.
- Update screenshots or recordings if a UI change is involved.
- Expect to iterate based on reviewer feedback.

## Questions

Need clarification? Open a discussion or reach out in the issue you are working on. We appreciate your help in making OptiCore better for everyone!


