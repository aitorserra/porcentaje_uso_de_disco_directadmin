#!/usr/bin/env bash
set -eu

PLUGIN_NAME="disk_partitions"
DA_PLUGINS_DIR="/usr/local/directadmin/plugins"
PLUGIN_DIR="${DA_PLUGINS_DIR}/${PLUGIN_NAME}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SOURCE_DIR="${SCRIPT_DIR}/${PLUGIN_NAME}"

if [ ! -d "$DA_PLUGINS_DIR" ]; then
    echo "Error: DirectAdmin plugins directory not found at ${DA_PLUGINS_DIR}"
    echo "Is DirectAdmin installed?"
    exit 1
fi

if [ ! -d "$SOURCE_DIR" ]; then
    echo "Error: Plugin source directory not found at ${SOURCE_DIR}"
    exit 1
fi

echo "Installing ${PLUGIN_NAME} plugin..."
rm -rf "${PLUGIN_DIR}"
mkdir -p "${PLUGIN_DIR}"
cp -R "${SOURCE_DIR}/." "${PLUGIN_DIR}/"

chmod 755 "${PLUGIN_DIR}/admin/index.html"
chmod 755 "${PLUGIN_DIR}/scripts/install.sh"
chmod 755 "${PLUGIN_DIR}/scripts/uninstall.sh"

echo "Plugin installed at ${PLUGIN_DIR}"
echo "Restarting DirectAdmin..."
systemctl restart directadmin 2>/dev/null || service directadmin restart 2>/dev/null || echo "Warning: Could not restart DirectAdmin automatically. Please restart it manually."
echo "Done! Access the plugin at: Admin Level > Disk Partitions"
