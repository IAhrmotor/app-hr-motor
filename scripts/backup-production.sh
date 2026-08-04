#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd)"

if [ -f "$PROJECT_ROOT/.env.docker" ]; then
  # shellcheck disable=SC1091
  set -a
  . "$PROJECT_ROOT/.env.docker"
  set +a
fi

BACKUP_ENV_FILE="${BACKUP_ENV_FILE:-$PROJECT_ROOT/backup.env}"
if [ -f "$BACKUP_ENV_FILE" ]; then
  # shellcheck disable=SC1091
  set -a
  . "$BACKUP_ENV_FILE"
  set +a
fi

BACKUP_LOCAL_PATH="${BACKUP_LOCAL_PATH:-/opt/hrmotor/backups}"
BACKUP_REMOTE_TARGET="${BACKUP_REMOTE_TARGET:-}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
BACKUP_AGE_RECIPIENT="${BACKUP_AGE_RECIPIENT:-}"
BACKUP_DB_SERVICE="${BACKUP_DB_SERVICE:-db}"
BACKUP_DB_NAME="${BACKUP_DB_NAME:-${DB_DATABASE:-}}"
BACKUP_DB_USER="${BACKUP_DB_USER:-${DB_USERNAME:-}}"
BACKUP_DB_PASSWORD="${BACKUP_DB_PASSWORD:-${DB_PASSWORD:-}}"
BACKUP_COMPOSE_FILE="${BACKUP_COMPOSE_FILE:-$PROJECT_ROOT/docker-compose.production.yml}"

BACKUP_TIMESTAMP="$(date -u +%Y-%m-%d_%H%M%S)"
BACKUP_BASENAME="hrmotor-app-prod-backup-${BACKUP_TIMESTAMP}"

mkdir -p "$BACKUP_LOCAL_PATH"

LOCAL_LOG_FILE="$BACKUP_LOCAL_PATH/${BACKUP_BASENAME}.log"
WORK_DIR="$(mktemp -d "$BACKUP_LOCAL_PATH/.${BACKUP_BASENAME}.XXXXXX")"
PACKAGE_LOG_FILE="$WORK_DIR/backup.log"
DATABASE_DUMP_FILE="$WORK_DIR/database.sql.gz"
FILES_ARCHIVE_FILE="$WORK_DIR/files.tar.gz"
MANIFEST_FILE="$WORK_DIR/manifest.json"
PACKAGE_TAR_FILE="$BACKUP_LOCAL_PATH/${BACKUP_BASENAME}.tar.gz"
FINAL_ENCRYPTED_FILE="$BACKUP_LOCAL_PATH/${BACKUP_BASENAME}.tar.gz.age"

PACKAGE_LOG_ACTIVE=1

json_escape() {
  local value="${1//\\/\\\\}"
  value="${value//\"/\\\"}"
  printf '%s' "$value"
}

json_array() {
  local items=("$@")
  local result='['
  local first=1
  local item

  for item in "${items[@]}"; do
    if [ $first -eq 0 ]; then
      result+=', '
    fi
    result+="\"$(json_escape "$item")\""
    first=0
  done

  result+=']'
  printf '%s' "$result"
}

human_size() {
  local bytes="$1"
  if command -v numfmt >/dev/null 2>&1; then
    numfmt --to=iec-i --suffix=B --format='%.1f' "$bytes"
    return
  fi

  printf '%s bytes' "$bytes"
}

log() {
  local line
  line="$(date -u +'%Y-%m-%d %H:%M:%S UTC') | $*"
  printf '%s\n' "$line" | tee -a "$LOCAL_LOG_FILE"

  if [ "${PACKAGE_LOG_ACTIVE:-0}" = "1" ]; then
    printf '%s\n' "$line" >> "$PACKAGE_LOG_FILE"
  fi
}

fail() {
  log "ERROR: $*"
  exit 1
}

cleanup() {
  rm -rf "$WORK_DIR"
  rm -f "$PACKAGE_TAR_FILE"
}

trap cleanup EXIT

require_command() {
  command -v "$1" >/dev/null 2>&1 || fail "Falta la dependencia requerida: $1"
}

require_command docker
require_command age
require_command rclone
require_command tar
require_command gzip

if command -v docker-compose >/dev/null 2>&1; then
  DOCKER_COMPOSE=(docker-compose -f "$BACKUP_COMPOSE_FILE")
else
  DOCKER_COMPOSE=(docker compose -f "$BACKUP_COMPOSE_FILE")
fi

if [ ! -f "$BACKUP_COMPOSE_FILE" ]; then
  fail "No se encuentra el archivo de compose: $BACKUP_COMPOSE_FILE"
fi

[ -n "$BACKUP_REMOTE_TARGET" ] || fail "BACKUP_REMOTE_TARGET no esta configurado"
[ -n "$BACKUP_AGE_RECIPIENT" ] || fail "BACKUP_AGE_RECIPIENT no esta configurado"
[ -n "$BACKUP_DB_NAME" ] || fail "BACKUP_DB_NAME no esta configurado"
[ -n "$BACKUP_DB_USER" ] || fail "BACKUP_DB_USER no esta configurado"
[ -n "$BACKUP_DB_PASSWORD" ] || fail "BACKUP_DB_PASSWORD no esta configurado"

SOURCE_PATHS=(
  "storage/app/public"
  "public/images/users/avatars"
  "public/images/dealerships"
  "public/revista"
)

INCLUDED_PATHS=()
MISSING_PATHS=()

for relative_path in "${SOURCE_PATHS[@]}"; do
  if [ -e "$PROJECT_ROOT/$relative_path" ]; then
    INCLUDED_PATHS+=("$relative_path")
  else
    MISSING_PATHS+=("$relative_path")
  fi
done

log "Inicio del backup de produccion: $BACKUP_BASENAME"
log "Ruta local: $BACKUP_LOCAL_PATH"
log "Destino remoto: $BACKUP_REMOTE_TARGET"
log "Retencion configurada: ${BACKUP_RETENTION_DAYS} dias"

if [ "${#INCLUDED_PATHS[@]}" -eq 0 ]; then
  log "No se encontraron rutas persistentes para incluir; se generara solo el volcado de base de datos."
else
  log "Rutas persistentes incluidas: ${#INCLUDED_PATHS[@]}"
fi

if [ "${#MISSING_PATHS[@]}" -gt 0 ]; then
  log "Rutas ausentes en esta instancia: ${MISSING_PATHS[*]}"
fi

log "Generando volcado completo de MariaDB en $DATABASE_DUMP_FILE"
"${DOCKER_COMPOSE[@]}" exec -T \
  -e BACKUP_DB_NAME="$BACKUP_DB_NAME" \
  -e BACKUP_DB_USER="$BACKUP_DB_USER" \
  -e MYSQL_PWD="$BACKUP_DB_PASSWORD" \
  "$BACKUP_DB_SERVICE" \
  sh -lc 'exec mariadb-dump --single-transaction --quick --routines --triggers --events --hex-blob --default-character-set=utf8mb4 -u"$BACKUP_DB_USER" "$BACKUP_DB_NAME"' \
  | gzip -9 > "$DATABASE_DUMP_FILE"

log "Empaquetando ficheros persistentes en $FILES_ARCHIVE_FILE"
if [ "${#INCLUDED_PATHS[@]}" -gt 0 ]; then
  tar --exclude='storage/app/public/.gitignore' -czf "$FILES_ARCHIVE_FILE" -C "$PROJECT_ROOT" "${INCLUDED_PATHS[@]}"
else
  tar -czf "$FILES_ARCHIVE_FILE" --files-from /dev/null
fi

log "Generando manifest tecnico en $MANIFEST_FILE"
cat > "$MANIFEST_FILE" <<EOF
{
  "backup_name": "$(json_escape "$BACKUP_BASENAME")",
  "generated_at_utc": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "project_root": "$(json_escape "$PROJECT_ROOT")",
  "compose_file": "$(json_escape "$BACKUP_COMPOSE_FILE")",
  "database": {
    "service": "$(json_escape "$BACKUP_DB_SERVICE")",
    "name": "$(json_escape "$BACKUP_DB_NAME")",
    "dump_file": "database.sql.gz"
  },
  "files": {
    "archive_file": "files.tar.gz",
    "included_paths": $(json_array "${INCLUDED_PATHS[@]}"),
    "missing_paths": $(json_array "${MISSING_PATHS[@]}")
  },
  "logs": {
    "file": "backup.log"
  }
}
EOF

DATABASE_BYTES="$(stat -c%s "$DATABASE_DUMP_FILE")"
FILES_BYTES="$(stat -c%s "$FILES_ARCHIVE_FILE")"
MANIFEST_BYTES="$(stat -c%s "$MANIFEST_FILE")"
LOG_BYTES="$(stat -c%s "$PACKAGE_LOG_FILE")"
APPROX_BYTES=$((DATABASE_BYTES + FILES_BYTES + MANIFEST_BYTES + LOG_BYTES))
log "Tamano aproximado previo al cifrado: $(human_size "$APPROX_BYTES")"

log "Creando archivo final temporal $PACKAGE_TAR_FILE"
tar -czf "$PACKAGE_TAR_FILE" -C "$WORK_DIR" database.sql.gz files.tar.gz manifest.json backup.log

PACKAGE_LOG_ACTIVE=0

log "Cifrando backup final con age en $FINAL_ENCRYPTED_FILE"
age -r "$BACKUP_AGE_RECIPIENT" -o "$FINAL_ENCRYPTED_FILE" "$PACKAGE_TAR_FILE"

FINAL_BYTES="$(stat -c%s "$FINAL_ENCRYPTED_FILE")"
log "Backup cifrado generado correctamente: $(human_size "$FINAL_BYTES")"

log "Subiendo backup cifrado a OneDrive/SharePoint"
rclone copy "$FINAL_ENCRYPTED_FILE" "$BACKUP_REMOTE_TARGET" --progress

log "Aplicando retencion local de ${BACKUP_RETENTION_DAYS} dias"
find "$BACKUP_LOCAL_PATH" -maxdepth 1 -type f \
  \( -name 'hrmotor-app-prod-backup-*.tar.gz.age' -o -name 'hrmotor-app-prod-backup-*.log' \) \
  -mtime +"$BACKUP_RETENTION_DAYS" -print -delete

log "Intentando retencion remota de ${BACKUP_RETENTION_DAYS} dias"
if ! rclone delete "$BACKUP_REMOTE_TARGET" --min-age "${BACKUP_RETENTION_DAYS}d" --include 'hrmotor-app-prod-backup-*.tar.gz.age'; then
  log "WARN: no se pudo aplicar la retencion remota; revisa la configuracion y permisos de rclone."
fi

log "Backup completado correctamente: $FINAL_ENCRYPTED_FILE"
