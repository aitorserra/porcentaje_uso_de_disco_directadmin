#!/usr/bin/env bash
set -eu

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$ROOT_DIR/disk_partitions"
DIST_DIR="$ROOT_DIR/dist"
VERSION="$(awk -F= '/^version=/{print $2}' "$PLUGIN_DIR/plugin.conf")"
ARCHIVE_NAME="disk_partitions-${VERSION}.tar.gz"
TMP_DIR="$(mktemp -d)"
STAGE_DIR="$TMP_DIR/disk_partitions"

cleanup() {
  rm -rf "$TMP_DIR"
}

trap cleanup EXIT

mkdir -p "$DIST_DIR" "$STAGE_DIR"
cp -R "$PLUGIN_DIR/." "$STAGE_DIR/"

chmod 755 "$STAGE_DIR/admin/index.html" "$STAGE_DIR/scripts/install.sh" "$STAGE_DIR/scripts/uninstall.sh"
chmod 644 "$STAGE_DIR/admin/index.php" "$STAGE_DIR/plugin.conf" "$STAGE_DIR/hooks/admin_txt.html"
rm -f "$STAGE_DIR/admin/.php-bin"

tar --sort=name --owner=0 --group=0 --numeric-owner -C "$TMP_DIR" -czf "$DIST_DIR/$ARCHIVE_NAME" disk_partitions

printf 'Created %s\n' "$DIST_DIR/$ARCHIVE_NAME"
