#!/bin/bash
set -e

PLUGIN_NAME="disk_partitions"
DA_PLUGINS_DIR="/usr/local/directadmin/plugins"
PLUGIN_DIR="${DA_PLUGINS_DIR}/${PLUGIN_NAME}"

if [ ! -d "$DA_PLUGINS_DIR" ]; then
    echo "Error: DirectAdmin plugins directory not found at ${DA_PLUGINS_DIR}"
    echo "Is DirectAdmin installed?"
    exit 1
fi

echo "Installing ${PLUGIN_NAME} plugin..."
cp -r "$(dirname "$0")/${PLUGIN_NAME}" "${PLUGIN_DIR}"

chmod 755 "${PLUGIN_DIR}/admin/index.html"
chmod 755 "${PLUGIN_DIR}/scripts/install.sh"
chmod 755 "${PLUGIN_DIR}/scripts/uninstall.sh"

echo "Plugin installed at ${PLUGIN_DIR}"
echo "Restarting DirectAdmin..."
systemctl restart directadmin 2>/dev/null || service directadmin restart 2>/dev/null || echo "Warning: Could not restart DirectAdmin automatically. Please restart it manually."
echo "Done! Access the plugin at: Admin Level > Disk Partitions"
