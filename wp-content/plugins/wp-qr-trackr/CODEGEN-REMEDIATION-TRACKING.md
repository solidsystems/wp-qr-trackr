### [2024-06-10] ESLint Ignore Fix
- Added `.eslintignore` to plugin directory to ignore `vendor/`, `node_modules/`, and `coverage/`.
- Updated `lint` script in `package.json` to use `--ignore-path .eslintignore`.
- CI now properly ignores non-source directories during linting, resolving previous failures. 