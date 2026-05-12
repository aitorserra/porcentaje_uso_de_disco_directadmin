#!/usr/bin/env bash
set -eu

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
DIST_DIR="$ROOT_DIR/dist"
ARCHIVE_PATH="${1:-}"

if [ -z "$ARCHIVE_PATH" ]; then
  ARCHIVE_PATH="$DIST_DIR/disk_partitions.tar.gz"
fi

if [ -z "$ARCHIVE_PATH" ] || [ ! -f "$ARCHIVE_PATH" ]; then
  echo "Error: package archive not found."
  exit 1
fi

LISTING="$(tar -tzvf "$ARCHIVE_PATH")"

require_entry() {
  entry="$1"
  if ! printf '%s\n' "$LISTING" | grep -F " $entry" >/dev/null 2>&1; then
    echo "Error: missing required archive entry: $entry"
    exit 1
  fi
}

require_mode() {
  entry="$1"
  expected="$2"
  actual="$(printf '%s\n' "$LISTING" | awk -v file="$entry" '$NF == file {print $1; exit}')"
  if [ "$actual" != "$expected" ]; then
    echo "Error: unexpected mode for $entry: got $actual expected $expected"
    exit 1
  fi
}

require_entry "./plugin.conf"
require_entry "./admin/index.html"
require_entry "./admin/index.php"
require_entry "./scripts/install.sh"
require_entry "./scripts/uninstall.sh"
require_entry "./hooks/admin_txt.html"
require_entry "./images/admin_icon.svg"

require_mode "./admin/index.html" "-rwxr-xr-x"
require_mode "./admin/index.php" "-rw-r--r--"
require_mode "./scripts/install.sh" "-rwxr-xr-x"
require_mode "./scripts/uninstall.sh" "-rwxr-xr-x"

echo "Package check passed: $ARCHIVE_PATH"
