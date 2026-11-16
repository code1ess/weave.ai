#!/usr/bin/env bash
set -euo pipefail

if [[ "$(id -u)" != 0 ]]; then
  echo 'Run this script with sudo on the host.' >&2
  exit 1
fi

project_root="$(cd -- "$(dirname -- "$0")/.." && pwd -P)"
getent passwd skynet >/dev/null
getent group www >/dev/null

for runtime_dir in "$project_root/storage" "$project_root/bootstrap/cache"; do
  [[ -d "$runtime_dir" ]] || { echo "Missing runtime directory: $runtime_dir" >&2; exit 1; }
  chown -R skynet:www "$runtime_dir"
  chmod -R u+rwX,g+rwX "$runtime_dir"
  find "$runtime_dir" -type d -exec chmod g+s {} +
done

echo 'Updated Laravel runtime permissions for the skynet owner and www PHP-FPM group.'
