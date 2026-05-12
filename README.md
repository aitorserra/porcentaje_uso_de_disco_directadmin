# Disk Usage Percentage - DirectAdmin Plugin

Plugin para el panel de administración DirectAdmin que muestra una tabla con todas las particiones de disco del servidor, indicando el tamaño total, el espacio ocupado y el porcentaje de espacio libre.

## Requisitos

- DirectAdmin instalado en el servidor
- `php-cli` disponible para el servicio de DirectAdmin
- `df` con soporte para `-P`, `-T` y `-k` (GNU coreutils en Linux)

## Instalación desde Plugin Manager

La forma recomendada es instalarlo como un plugin comprimido desde el **Plugin Manager** de DirectAdmin, sin copiar archivos a mano.

### Descarga directa

Puedes descargar directamente el paquete listo para instalar aquí:

- `https://github.com/aitorserra/porcentaje_uso_de_disco_directadmin/raw/main/dist/disk_partitions-1.1.0.tar.gz`

### 1. Generar el paquete `.tar.gz`

```bash
./package.sh
```

Esto crea un fichero en `dist/` con este formato:

```text
dist/disk_partitions-1.1.0.tar.gz
```

El paquete ya incluye:

- estructura correcta para DirectAdmin
- `plugin.conf`
- scripts de lifecycle
- permisos ejecutables en `admin/index.html` y `scripts/*.sh`
- detección automática y persistencia local de la ruta de `php-cli` durante la instalación

### 2. Verificar el paquete antes de subirlo

```bash
./check-package.sh
```

La comprobación valida que el `.tar.gz` contiene:

- estructura `disk_partitions/...`
- `plugin.conf`
- punto de entrada `admin/index.html`
- renderer `admin/index.php`
- scripts `scripts/install.sh` y `scripts/uninstall.sh`
- icono `images/admin_icon.svg`
- permisos ejecutables correctos en los entrypoints

### 3. Instalarlo desde DirectAdmin

En **Admin Level > Plugin Manager**:

1. usa la opción de subir/instalar plugin desde archivo
2. selecciona el `.tar.gz` generado
3. deja que DirectAdmin ejecute `disk_partitions/scripts/install.sh`

No hace falta ningún `chmod`, copia manual ni ajuste posterior si el servidor tiene `php-cli` y `df`.

## Instalación manual

```bash
# Clonar el repositorio
git clone git@github.com:aitorserra/porcentaje_uso_de_disco_directadmin.git

# Copiar el plugin al directorio de plugins de DirectAdmin
cp -r porcentaje_uso_de_disco_directadmin/disk_partitions /usr/local/directadmin/plugins/

# Ejecutar el script de instalación del plugin
/usr/local/directadmin/plugins/disk_partitions/scripts/install.sh

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
| Type    | Tipo de filesystem (ej: `xfs`, `ext4`) |
| Mount   | Punto de montaje (ej: `/`, `/home`) |
| Total   | Tamaño total de la partición |
| Used    | Espacio ocupado |
| Free    | Espacio disponible |
| Free %  | Porcentaje de espacio libre con barra visual en escala de grises |

## Diseño accesible

La barra de porcentaje libre usa una **escala de grises**: más claro indica menos espacio libre y más oscuro indica más espacio libre. Esto es legible para personas con daltonismo.

## Comportamiento

- El plugin muestra solo filesystems relevantes para capacidad real y filtra pseudo-filesystems como `tmpfs`, `proc`, `sysfs`, `overlay` o `squashfs`.
- Las filas se ordenan por mayor porcentaje de uso para destacar antes las particiones con más riesgo.
- Si `php` o `df` no están disponibles, el plugin muestra un error visible en la interfaz en vez de fallar en silencio.
- La instalación guarda la ruta detectada de `php-cli` en el plugin para no depender del `PATH` del servicio de DirectAdmin.

## Desinstalación

```bash
/usr/local/directadmin/plugins/disk_partitions/scripts/uninstall.sh
rm -rf /usr/local/directadmin/plugins/disk_partitions
systemctl restart directadmin
```

## Licencia

MIT
