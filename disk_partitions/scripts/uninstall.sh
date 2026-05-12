#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PLUGIN_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
PHP_BIN_FILE="$PLUGIN_DIR/admin/.php-bin"

rm -f "$PHP_BIN_FILE"

echo "Disk Partitions plugin removed."
exit 0
