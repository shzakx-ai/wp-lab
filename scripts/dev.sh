#!/usr/bin/env bash
# Boot WordPress Playground with the lab theme mounted and the CI blueprint applied.
# Usage: bash scripts/dev.sh [port]   (default port: 9400)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${1:-9400}"

exec npx --yes @wp-playground/cli@latest server \
	--mount="$ROOT/wp-content/themes/lab-theme:/wordpress/wp-content/themes/lab-theme" \
	--blueprint="$ROOT/blueprint.json" \
	--port="$PORT"
