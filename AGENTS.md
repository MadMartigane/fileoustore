# Instructions for AI Agents

## Code Modification Rules

- If the code is modified, it must systematically check the code via the linter and the formatter.
  - Run `pnpm run lint` to ensure no lint errors or warnings.
  - Run `pnpm run format` to format the code according to the project's standards.
  - Both commands must pass without errors before considering the task complete.
  - If issues are found, fix them immediately and re-run the checks.