#!/usr/bin/env bash
# Egy paranccsal telepíti és elindítja a lokális DEV stacket.
#
# Használat: ./install.sh [projekt_nev]
#
# Első futáskor létrehozza a root .env-et a .env.example alapján (projektnév,
# véletlen jelszavak, szabad portok), majd buildel, telepíti a Composer
# függőségeket, elindítja a konténereket, lefuttatja a migrationöket és
# megvárja, hogy a frontend, az admin és az API válaszoljon.
# Újrafuttatható: meglévő .env-et nem ír felül.
set -euo pipefail

cd "$(dirname "$0")"

ENV_FILE=".env"
WAIT_TIMEOUT="${WAIT_TIMEOUT:-600}"

log() {
  printf '\n==> %s\n' "$*"
}

fail() {
  printf '\nHIBA: %s\n' "$*" >&2
  exit 1
}

random_secret() {
  od -An -N24 -tx1 /dev/urandom | tr -d ' \n'
}

get_env_value() {
  awk -F= -v key="$1" '
    $1 == key {
      value = substr($0, length(key) + 2)
      gsub(/^"|"$/, "", value)
      print value
      exit
    }
  ' "$ENV_FILE"
}

set_env_value() {
  local key="$1"
  local value="$2"
  local tmp_file

  tmp_file="$(mktemp)"
  awk -v key="$key" -v value="$value" '
    BEGIN { updated = 0 }
    $0 ~ "^" key "=" {
      print key "=" value
      updated = 1
      next
    }
    { print }
    END {
      if (updated == 0) {
        print key "=" value
      }
    }
  ' "$ENV_FILE" > "$tmp_file"
  mv "$tmp_file" "$ENV_FILE"
}

sanitize_name() {
  printf '%s' "$1" \
    | tr '[:upper:]' '[:lower:]' \
    | sed -E 's/[^a-z0-9_-]+/_/g; s/^[^a-z0-9]+//; s/[_-]+$//'
}

create_env_file() {
  local project_name="$1"
  local db_name
  local db_user
  local db_password
  local http_port

  cp .env.example "$ENV_FILE"
  chmod 600 "$ENV_FILE"

  # MySQL: adatbázisnév max 64, felhasználónév max 32 karakter.
  db_name="$(printf '%s' "$project_name" | tr '-' '_' | cut -c1-32)"
  db_user="$(printf '%s' "$db_name" | cut -c1-27)_user"
  db_password="$(random_secret)"

  set_env_value PROJECT_NAME "$project_name"
  set_env_value COMPOSE_PROJECT_NAME "$project_name"
  set_env_value APP_SECRET "$(random_secret)"
  set_env_value MYSQL_ROOT_PASSWORD "$(random_secret)"
  set_env_value MYSQL_DATABASE "$db_name"
  set_env_value MYSQL_USER "$db_user"
  set_env_value MYSQL_PASSWORD "$db_password"
  set_env_value DATABASE_URL \
    "mysql://${db_user}:${db_password}@mysql:$(get_env_value MYSQL_INTERNAL_PORT)/${db_name}?serverVersion=8.4.8&charset=utf8mb4"

  bash ./local-port-finder.sh "$ENV_FILE"

  http_port="$(get_env_value HTTP_PORT)"
  set_env_value APP_FRONTEND_URL "http://localhost:${http_port}"
  set_env_value APP_ADMIN_URL "http://localhost:${http_port}/admin"
}

wait_for_http() {
  local label="$1"
  local url="$2"
  local started_at
  started_at="$(date +%s)"

  printf '    %-9s %s ' "$label" "$url"
  until curl -fsS -o /dev/null "$url" 2>/dev/null; do
    if (( $(date +%s) - started_at > WAIT_TIMEOUT )); then
      printf 'IDŐTÚLLÉPÉS\n'
      fail "$label nem válaszolt ${WAIT_TIMEOUT} mp alatt. Logok: docker compose logs"
    fi
    printf '.'
    sleep 5
  done
  printf ' OK\n'
}

check_containers() {
  local not_running
  not_running="$(docker compose ps -a --format '{{.Name}} {{.State}}' | awk '$2 != "running"')"

  if [[ -n "$not_running" ]]; then
    printf '%s\n' "$not_running" >&2
    fail "Nem minden konténer fut."
  fi
}

command -v docker >/dev/null 2>&1 || fail "A docker nincs telepítve."
docker info >/dev/null 2>&1 || fail "A Docker daemon nem fut."
docker compose version >/dev/null 2>&1 || fail "A docker compose plugin hiányzik."
command -v curl >/dev/null 2>&1 || fail "A curl nincs telepítve."

if [[ -f "$ENV_FILE" ]]; then
  log "Meglévő $ENV_FILE használata (nem írom felül)"
else
  project_name="$(sanitize_name "${1:-$(basename "$PWD")}")"
  [[ -n "$project_name" ]] || fail "Érvénytelen projektnév."
  log "$ENV_FILE létrehozása (projekt: $project_name)"
  create_env_file "$project_name"
fi

HTTP_PORT="$(get_env_value HTTP_PORT)"
PHPMYADMIN_PORT="$(get_env_value PHPMYADMIN_PORT)"

log "Docker image-ek építése"
docker compose build

log "Composer függőségek telepítése"
docker compose run --rm --no-deps php sh -c \
  'composer install --no-interaction --prefer-dist && mkdir -p var && chmod -R a+rwX var'

log "Konténerek indítása"
docker compose up -d --wait --wait-timeout 300

log "Adatbázis migrationök"
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

log "Várakozás a szolgáltatásokra (a Nuxt konténerek induláskor npm ci-t futtatnak, ez pár perc)"
wait_for_http "API" "http://localhost:${HTTP_PORT}/api/health"
wait_for_http "Frontend" "http://localhost:${HTTP_PORT}/"
wait_for_http "Admin" "http://localhost:${HTTP_PORT}/admin/"
wait_for_http "Mailpit" "http://localhost:${HTTP_PORT}/mailpit/"

check_containers

log "Kész"
cat <<EOF
    Frontend:    http://localhost:${HTTP_PORT}/
    Admin:       http://localhost:${HTTP_PORT}/admin/
    API health:  http://localhost:${HTTP_PORT}/api/health
    Mailpit:     http://localhost:${HTTP_PORT}/mailpit/
    phpMyAdmin:  http://localhost:${PHPMYADMIN_PORT}/

    Leállítás:   docker compose down
EOF
