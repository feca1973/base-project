#!/usr/bin/env bash
set -euo pipefail

ENV_FILE="${1:-.env.example}"
PORT_KEYS=("HTTP_PORT" "PHPMYADMIN_PORT")
ASSIGNED_PORTS=""
ORIGINAL_PORTS=""

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing env file: $ENV_FILE" >&2
  exit 1
fi

if [[ "$ENV_FILE" == ".env.example" && -f ".env" ]]; then
  echo ".env already exists. This script updates .env.example by default; run './local-port-finder.sh .env' if you want to update the active local env file." >&2
fi

is_number() {
  [[ "$1" =~ ^[0-9]+$ ]]
}

is_port_in_use() {
  local port="$1"

  if command -v lsof >/dev/null 2>&1; then
    lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1
    return $?
  fi

  if command -v nc >/dev/null 2>&1; then
    nc -z 127.0.0.1 "$port" >/dev/null 2>&1
    return $?
  fi

  return 1
}

is_assigned_port() {
  local port="$1"

  [[ " $ASSIGNED_PORTS " == *" $port "* ]]
}

is_original_port_for_other_key() {
  local key="$1"
  local port="$2"
  local entry
  local entry_key
  local entry_port

  for entry in $ORIGINAL_PORTS; do
    entry_key="${entry%%:*}"
    entry_port="${entry#*:}"

    if [[ "$entry_key" != "$key" && "$entry_port" == "$port" ]]; then
      return 0
    fi
  done

  return 1
}

get_env_value() {
  local key="$1"

  awk -F= -v key="$key" '
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

find_free_port() {
  local key="$1"
  local start_port="$2"
  local candidate="$start_port"

  while (( candidate <= 65535 )); do
    if ! is_assigned_port "$candidate" \
      && ! is_original_port_for_other_key "$key" "$candidate" \
      && ! is_port_in_use "$candidate"; then
      echo "$candidate"
      return 0
    fi

    ((candidate++))
  done

  echo "No free port found for $key starting from $start_port" >&2
  exit 1
}

for key in "${PORT_KEYS[@]}"; do
  current_value="$(get_env_value "$key")"

  if [[ -z "$current_value" ]]; then
    echo "$key is missing from $ENV_FILE" >&2
    exit 1
  fi

  if ! is_number "$current_value"; then
    echo "$key must be a numeric port in $ENV_FILE, got: $current_value" >&2
    exit 1
  fi

  ORIGINAL_PORTS="$ORIGINAL_PORTS $key:$current_value"
done

for key in "${PORT_KEYS[@]}"; do
  current_value="$(get_env_value "$key")"

  if is_assigned_port "$current_value" || is_port_in_use "$current_value"; then
    new_value="$(find_free_port "$key" "$((current_value + 1))")"
    set_env_value "$key" "$new_value"
    ASSIGNED_PORTS="$ASSIGNED_PORTS $new_value"
    echo "$key=$current_value is busy or already assigned, updated to $new_value in $ENV_FILE"
  else
    ASSIGNED_PORTS="$ASSIGNED_PORTS $current_value"
    echo "$key=$current_value is available"
  fi
done

echo "Port check finished for $ENV_FILE"
