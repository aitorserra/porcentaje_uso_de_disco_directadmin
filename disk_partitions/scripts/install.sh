#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PLUGIN_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
ADMIN_ENTRY="$PLUGIN_DIR/admin/index.html"
ADMIN_PHP="$PLUGIN_DIR/admin/index.php"
PHP_BIN_FILE="$PLUGIN_DIR/admin/.php-bin"

if [ ! -f "$ADMIN_ENTRY" ]; then
  echo "Error: missing plugin entry point: $ADMIN_ENTRY"
  exit 1
fi

if [ ! -f "$ADMIN_PHP" ]; then
  echo "Error: missing PHP renderer: $ADMIN_PHP"
  exit 1
fi

chmod 755 "$ADMIN_ENTRY" "$SCRIPT_DIR/install.sh" "$SCRIPT_DIR/uninstall.sh"
chmod 644 "$ADMIN_PHP"

if ! command -v df >/dev/null 2>&1; then
  echo "Error: df command is not available on this server."
  exit 1
fi

if ! df -PT -k >/dev/null 2>&1; then
  echo "Error: this server's df command does not support the required -P -T -k options."
  exit 1
fi

PHP_BIN=${PHP_BIN:-}

if [ -z "$PHP_BIN" ]; then
  if command -v php >/dev/null 2>&1; then
    PHP_BIN=$(command -v php)
  elif [ -x /usr/local/bin/php ]; then
    PHP_BIN=/usr/local/bin/php
  elif [ -x /usr/bin/php ]; then
    PHP_BIN=/usr/bin/php
  fi
fi

if [ -z "$PHP_BIN" ] || [ ! -x "$PHP_BIN" ]; then
  echo "Error: PHP CLI binary not found. Install php-cli or set PHP_BIN before installation."
  exit 1
fi

printf '%s\n' "$PHP_BIN" > "$PHP_BIN_FILE"
chmod 644 "$PHP_BIN_FILE"

echo "Disk Partitions plugin installed successfully."
exit 0
