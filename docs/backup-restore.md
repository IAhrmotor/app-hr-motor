# Restauracion de backups de produccion

## Advertencia importante

Sin la clave privada de `age` no sera posible restaurar el contenido del backup cifrado.

## Archivo de backup

Cada ejecucion genera un unico fichero cifrado:

```text
hrmotor-app-prod-backup-YYYY-MM-DD_HHMMSS.tar.gz.age
```

Dentro del `.tar.gz` cifrado viajan:

- `database.sql.gz`
- `files.tar.gz`
- `manifest.json`
- `backup.log`

## Paso 1: descargar el backup desde OneDrive/SharePoint

Descarga el fichero `.tar.gz.age` desde la carpeta corporativa:

- `Departamento IT/Backups APP`

## Paso 2: descifrar con age en Linux

```bash
age -d -i /secure/path/hrmotor-backup-key.txt hrmotor-app-prod-backup-YYYY-MM-DD_HHMMSS.tar.gz.age > hrmotor-app-prod-backup-YYYY-MM-DD_HHMMSS.tar.gz
```

## Paso 2: descifrar con age.exe en Windows PowerShell

```powershell
age.exe -d -i "C:\Backups\keys\hrmotor-backup-key.txt" "C:\Backups\hrmotor-app-prod-backup-YYYY-MM-DD_HHMMSS.tar.gz.age" > "C:\Backups\hrmotor-app-prod-backup-YYYY-MM-DD_HHMMSS.tar.gz"
```

## Paso 3: extraer el archivo tar.gz

En Linux:

```bash
mkdir -p /tmp/hrmotor-restore
tar -xzf hrmotor-app-prod-backup-YYYY-MM-DD_HHMMSS.tar.gz -C /tmp/hrmotor-restore
```

En Windows PowerShell:

```powershell
tar -xzf "C:\Backups\hrmotor-app-prod-backup-YYYY-MM-DD_HHMMSS.tar.gz" -C "C:\Backups\restore"
```

## Paso 4: restaurar la base de datos

El archivo `database.sql.gz` contiene el dump completo de MariaDB.

En Linux, para una instancia MariaDB en Docker:

```bash
gunzip -c /tmp/hrmotor-restore/database.sql.gz | docker compose -f docker-compose.production.yml exec -T db sh -lc 'mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

En Windows, si restauras contra un servidor MariaDB accesible por red:

```powershell
gunzip -c "C:\Backups\restore\database.sql.gz" | mariadb -h 127.0.0.1 -u root -p hrmotor
```

Adapta el usuario, la base de datos y el host al entorno de restauracion real.

## Paso 5: restaurar los ficheros persistentes

El archivo `files.tar.gz` contiene las rutas persistentes detectadas en produccion. Extraelo en la raiz del proyecto para recuperar la misma estructura de carpetas:

```bash
tar -xzf /tmp/hrmotor-restore/files.tar.gz -C /var/www/hrmotor
```

Las rutas comunes incluyen:

- `storage/app/public`
- `public/images/users/avatars`
- `public/images/dealerships`
- `public/revista`

## Paso 6: volver a aplicar la retencion legal de mensajes

Despues de restaurar un backup antiguo, ejecuta de nuevo la tarea de retencion de mensajes de 6 meses para volver a aplicar la politica legal de conservacion:

```bash
php artisan chat:purge-expired-messages
```

Si la aplicacion esta en Docker, ejecuta el comando dentro del contenedor de la app o del scheduler.
