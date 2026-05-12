# Disk Usage Percentage - DirectAdmin Plugin

Plugin para el panel de administración DirectAdmin que muestra una tabla con todas las particiones de disco del servidor, indicando el tamaño total, el espacio ocupado y el porcentaje de espacio libre.

## Requisitos

- DirectAdmin instalado en el servidor
- PHP disponible en el `PATH` del sistema

## Instalación

```bash
# Clonar el repositorio
git clone git@github.com:aitorserra/porcentaje_uso_de_disco_directadmin.git

# Copiar el plugin al directorio de plugins de DirectAdmin
cp -r Disk-usage-percentage-DirectAdmin/disk_partitions /usr/local/directadmin/plugins/

# Establecer permisos
chmod 755 /usr/local/directadmin/plugins/disk_partitions/admin/index.html
chmod 755 /usr/local/directadmin/plugins/disk_partitions/scripts/install.sh
chmod 755 /usr/local/directadmin/plugins/disk_partitions/scripts/uninstall.sh

# Reiniciar DirectAdmin
systemctl restart directadmin
```

O usando el script de instalación incluido:

```bash
bash install.sh
```

## Acceso

El plugin está disponible solo para el nivel **admin** de DirectAdmin:

- Menú lateral: **Disk Partitions**
- URL directa: `https://tudominio.com:2222/CMD_PLUGINS_ADMIN/disk_partitions`

## Columnas de la tabla

| Columna | Descripción |
|---------|-------------|
| Device  | Dispositivo de bloque (ej: `/dev/sda1`) |
| Mount   | Punto de montaje (ej: `/`, `/home`) |
| Total   | Tamaño total de la partición |
| Used    | Espacio ocupado |
| Free    | Espacio disponible |
| Free %  | Porcentaje de espacio libre con barra visual en escala de grises |

## Diseño accesible

La barra de porcentaje libre usa una **escala de grises**: más claro indica menos espacio libre y más oscuro indica más espacio libre. Esto es legible para personas con daltonismo.

## Desinstalación

```bash
rm -rf /usr/local/directadmin/plugins/disk_partitions
systemctl restart directadmin
```

## Licencia

MIT
