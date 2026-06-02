# Configuracion del backup de produccion

Este proyecto usa un script Bash para generar un unico backup diario cifrado y subirlo a OneDrive/SharePoint con `rclone`.

## Ubicacion

- Script: `scripts/backup-production.sh`

## Variables de entorno

Configura estas variables en el servidor, por ejemplo en `/etc/hrmotor/backup.env` o en el mismo `cron`:

```bash
BACKUP_LOCAL_PATH=/opt/hrmotor/backups
BACKUP_REMOTE_TARGET="onedrive:Departamento IT/Backups APP"
BACKUP_RETENTION_DAYS=30
BACKUP_AGE_RECIPIENT="age1..."
BACKUP_DB_SERVICE="db"
BACKUP_DB_NAME="hrmotor"
BACKUP_DB_USER="hrmotor"
BACKUP_DB_PASSWORD="..."
```

Puedes usar como base [`backup.env.example`](/C:/Users/Usuario/Documents/proyectos/app-hr-motor/backup.env.example) y renombrarlo a `backup.env` en el servidor.

El script tambien puede leer `.env.docker` del proyecto para reutilizar `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` si ya estan definidos ahi, y luego sobreescribirlos con `backup.env` si existe.

## Dependencias en el servidor

- `docker` con soporte `docker compose`
- `age`
- `rclone`
- `tar`
- `gzip`

Si `age` no esta instalado:

- En Debian o Ubuntu, instala el paquete disponible en tu repositorio o desde la distribucion oficial.
- En Red Hat, AlmaLinux o Rocky, usa el paquete equivalente de tu repositorio o binario oficial.

No guardes la clave privada age en GitHub, en OneDrive/SharePoint, ni dentro del backup. La clave privada debe custodiarla IT fuera del proyecto.

## Rclone y OneDrive/SharePoint

1. Configura el remote con:

```bash
rclone config
```

2. Crea o reutiliza un remote llamado `onedrive`.
3. Autentica con la cuenta corporativa de IT o con la app de Microsoft Entra ID que prefiera IT.
4. Valida la ruta antes de automatizar:

```bash
rclone lsd onedrive:
rclone lsd "onedrive:Departamento IT"
rclone copy archivo.age "onedrive:Departamento IT/Backups APP"
```

Si la ruta exacta no existe, ajusta `BACKUP_REMOTE_TARGET` hasta que `rclone copy` funcione correctamente.

## Ejecucion manual

Desde la raiz del proyecto:

```bash
bash scripts/backup-production.sh
```

## Ejecucion diaria con cron

Ejemplo:

```cron
30 2 * * * . /etc/hrmotor/backup.env && cd /opt/hrmotor/app-hr-motor && bash scripts/backup-production.sh >> /var/log/hrmotor-backup-cron.log 2>&1
```

Esto ejecuta el backup cada dia a las `02:30` hora local del servidor. Si prefieres otra franja, cambia la expresion `cron`.

El log tecnico local del backup se guarda junto al archivo generado en `BACKUP_LOCAL_PATH`.

## Conexion con OneDrive/SharePoint

1. Instala `rclone` en el servidor.
2. Ejecuta:

```bash
rclone config
```

3. Crea un remote nuevo llamado `onedrive`.
4. Elige el backend `Microsoft OneDrive`.
5. `rclone` te pedira autorizar en el navegador y obtener un token de Microsoft.
6. Si el servidor no tiene navegador, haz la autorizacion desde una maquina con navegador usando `rclone authorize onedrive` y pega el token resultante en la configuracion del servidor.
7. Valida la ruta corporativa con:

```bash
rclone lsd onedrive:
rclone lsd "onedrive:Departamento IT"
rclone copy archivo.age "onedrive:Departamento IT/Backups APP"
```

La documentacion oficial de `rclone` indica que la configuracion inicial de OneDrive obtiene un token de Microsoft en el navegador, y que `rclone authorize` sirve para autorizar un rclone remoto o headless desde una maquina con navegador.
