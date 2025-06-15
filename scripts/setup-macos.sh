#!/bin/bash
set -e

# macOS Setup Script for wp-qr-trackr
# Ensures Homebrew, PHP, and Xdebug (via fix-pecl-xdebug.sh) are installed
# Compatible with both ARM (Apple Silicon) and x86 (Intel)
# Works in VSCode and Cursor terminals

# Function to print section headers
section() {
  echo
  echo "=============================="
  echo "$1"
  echo "=============================="
}

section "Checking Homebrew installation"
if ! command -v brew >/dev/null 2>&1; then
  echo "[INFO] Homebrew not found. Installing..."
  /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
else
  echo "[OK] Homebrew is installed."
fi

section "Checking PHP installation"
if ! command -v php >/dev/null 2>&1; then
  echo "[INFO] PHP not found. Installing latest PHP via Homebrew..."
  brew install php
else
  echo "[OK] PHP is installed."
fi

section "Running Xdebug/PECL Homebrew Fix"
if [ -f "fix-pecl-xdebug.sh" ]; then
  chmod +x fix-pecl-xdebug.sh
  ./fix-pecl-xdebug.sh
else
  echo "[ERROR] fix-pecl-xdebug.sh not found in current directory. Please ensure it is present."
  exit 1
fi

section "Setup Complete"
echo "Your macOS development environment is ready!" 