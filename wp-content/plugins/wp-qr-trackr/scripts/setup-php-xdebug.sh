#!/bin/bash

# Ensure script is run with bash
if [ -z "$BASH_VERSION" ]; then
  echo "Please run this script with bash"
  exit 1
fi

# Check for PHP
if ! command -v php &> /dev/null; then
  echo "PHP is not installed. Please install PHP first."
  exit 1
fi

# Check for Xdebug
if php -m | grep -q xdebug; then
  echo "Xdebug is already installed."
else
  echo "Xdebug not found. Installing via PECL..."
  if ! command -v pecl &> /dev/null; then
    echo "PECL is not installed. Please install PECL (e.g., via Homebrew: brew install php@8.2-pecl) and rerun."
    exit 1
  fi
  pecl install xdebug || { echo "Xdebug installation failed."; exit 1; }
fi

# Enable Xdebug in php.ini if not already enabled
echo "Ensuring Xdebug is enabled in php.ini..."
PHP_INI=$(php --ini | grep ".ini" | head -n 1 | awk '{print $4}')
if ! grep -q "xdebug.so" "$PHP_INI"; then
  echo "zend_extension=\"xdebug.so\"" | sudo tee -a "$PHP_INI"
  echo "xdebug.mode=coverage" | sudo tee -a "$PHP_INI"
fi

# Print Xdebug status
php -v
php -m | grep xdebug && echo "Xdebug is enabled!" || echo "Xdebug is NOT enabled. Please check your php.ini." 