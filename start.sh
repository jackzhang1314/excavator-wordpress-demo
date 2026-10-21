#!/usr/bin/env bash
set -euo pipefail
# Keep checksum and archive tools portable when a parent app supplies a locale
# that is not installed on this OS. This affects this launcher process only.
export LC_ALL=C
cd "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"

node_ok() { command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1 && node -e 'const [a,b]=process.versions.node.split(".").map(Number); process.exit(a>22||(a===22&&b>=13)?0:1)' >/dev/null 2>&1; }
if [[ "${DEMO_USE_PORTABLE_NODE:-0}" == 1 ]] || ! node_ok; then
  case "$(uname -s)-$(uname -m)" in
    Darwin-arm64) platform=darwin-arm64; checksum=cc04a76a09f79290194c0646f48fec40354d88969bec467789a5d55dd097f949 ;;
    Darwin-x86_64) platform=darwin-x64; checksum=00df9c5df3e4ec6848c26b70fb47bf96492f342f4bed6b17f12d99b3a45eeecc ;;
    Linux-x86_64) platform=linux-x64; checksum=eeaccb0378b79406f2208e8b37a62479c70595e20be6b659125eb77dd1ab2a29 ;;
    Linux-aarch64|Linux-arm64) platform=linux-arm64; checksum=4181609e03dcb9880e7e5bf956061ecc0503c77a480c6631d868cb1f65a2c7dd ;;
    *) echo 'Unsupported platform. Ask Codex to install Node.js 22.13+ and run npm ci, then npm start.' >&2; exit 1 ;;
  esac
  directory=".tools/node-v22.20.0-$platform"
  if [[ ! -x "$directory/bin/node" ]]; then
    mkdir -p .tools
    archive=".tools/node-v22.20.0-$platform.tar.gz"
    echo 'Preparing a project-local Node.js runtime (no global installation).'
    curl --fail --location --retry 2 --output "$archive" "https://nodejs.org/dist/v22.20.0/node-v22.20.0-$platform.tar.gz"
    if command -v shasum >/dev/null 2>&1; then actual="$(shasum -a 256 "$archive" | cut -d ' ' -f 1)"; else actual="$(sha256sum "$archive" | cut -d ' ' -f 1)"; fi
    if [[ "$actual" != "$checksum" ]]; then echo 'Node.js checksum mismatch. Download stopped.' >&2; exit 1; fi
    tar -xzf "$archive" -C .tools
    rm -- "$archive"
  fi
  export PATH="$PWD/$directory/bin:$PATH"
fi

mkdir -p .tools
lock_hash="$(node -e 'process.stdout.write(require("node:crypto").createHash("sha256").update(require("node:fs").readFileSync("package-lock.json")).digest("hex"))')"
saved_hash="$(cat .tools/installed-lock.sha256 2>/dev/null || true)"
if [[ ! -d node_modules/@wp-playground/wordpress ]] || [[ "$saved_hash" != "$lock_hash" ]]; then
  npm ci --ignore-scripts --no-audit --no-fund
  echo "$lock_hash" > .tools/installed-lock.sha256
fi
exec node scripts/start.mjs "$@"
