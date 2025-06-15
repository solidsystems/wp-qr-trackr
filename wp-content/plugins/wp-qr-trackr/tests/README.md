# Testing & Linting

## PHP CodeSniffer (PHPCS)

- Install dependencies:
  ```sh
  composer install
  ```
- Run PHPCS for all plugin code and tests:
  ```sh
  composer phpcs
  ```
  This uses the local config and works on Mac, Linux, and CI. No global PHPCS install is needed.

## JavaScript & CSS Linting

- Run JS linting:
  ```sh
  yarn lint
  ```
- Run CSS linting:
  ```sh
  yarn stylelint
  ```

## PHPUnit

- (Add instructions here if PHPUnit tests are present and how to run them) 