#!/bin/bash
set -e

# Variables
PHP_VERSION="8.4.8"
CELLAR_PATH="/opt/homebrew/Cellar/php/$PHP_VERSION"
PECL_SYMLINK="$CELLAR_PATH/pecl"
PECL_TARGET="/opt/homebrew/lib/php/pecl"
PECL_SUBDIR="20240924"

# 1. Check symlink
if [ -L "$PECL_SYMLINK" ]; then
  echo "[OK] $PECL_SYMLINK is a symlink."
else
  echo "[ERROR] $PECL_SYMLINK is not a symlink. Exiting."
  exit 1
fi

# 2. Ensure target directory exists
if [ ! -d "$PECL_TARGET/$PECL_SUBDIR" ]; then
  echo "[INFO] Creating $PECL_TARGET/$PECL_SUBDIR..."
  sudo mkdir -p "$PECL_TARGET/$PECL_SUBDIR"
else
  echo "[OK] $PECL_TARGET/$PECL_SUBDIR already exists."
fi

# 3. Fix permissions
sudo chown -R $(whoami) "$PECL_TARGET"
echo "[OK] Permissions fixed for $PECL_TARGET."

# 4. Try installing xdebug with pecl
if command -v pecl >/dev/null 2>&1; then
  echo "[INFO] Attempting to install xdebug with pecl..."
  if pecl install xdebug; then
    echo "[SUCCESS] xdebug installed via pecl."
    exit 0
  else
    echo "[WARN] pecl install failed. Will try Homebrew."
  fi
else
  echo "[WARN] pecl not found. Will try Homebrew."
fi

# 5. Fallback: Homebrew install
if brew list | grep -q php-xdebug; then
  echo "[OK] php-xdebug already installed via Homebrew."
else
  echo "[INFO] Installing php-xdebug via Homebrew..."
  brew install php-xdebug
fi

echo "[DONE] Xdebug installation script complete." 