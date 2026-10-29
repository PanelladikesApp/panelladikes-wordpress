#!/bin/sh
# Builds the release zip from the current working tree, using .distignore.
#
#   sh scripts/build-package.sh <output directory>
#
# Produces <output directory>/panelladikes-widgets.zip (root folder
# panelladikes-widgets/) and panelladikes-widgets.zip.sha256. The build
# (build/) and the translations (languages/*.mo, *.json) must already exist.
set -eu

[ $# -eq 1 ] || { echo "Usage: sh scripts/build-package.sh <output directory>" >&2; exit 1; }
cd "$(dirname "$0")/.."
ROOT=$(pwd)
mkdir -p "$1"
OUT=$(cd "$1" && pwd)
STAGE="$OUT/stage"
NAME=panelladikes-widgets

rm -rf "$STAGE" "$OUT/$NAME.zip" "$OUT/$NAME.zip.sha256"
mkdir -p "$STAGE/$NAME"
# --no-links: a symbolic link in the repository must never reach the package.
rsync -a --no-links --exclude-from=.distignore "$ROOT/" "$STAGE/$NAME/"

for required in \
	panelladikes-widgets.php uninstall.php readme.txt README.md LICENSE \
	build/index.js build/index.css build/index.asset.php build/block.json \
	contract/embed-contract.v2.json includes/updater/bootstrap.php \
	languages/panelladikes-widgets-el.po languages/panelladikes-widgets-el.mo; do
	[ -f "$STAGE/$NAME/$required" ] || { echo "Missing from the package: $required" >&2; exit 1; }
done
ls "$STAGE/$NAME"/languages/panelladikes-widgets-el-*.json >/dev/null 2>&1 || { echo "Missing from the package: translation JSON" >&2; exit 1; }
for forbidden in vendor tests scripts src docs node_modules .git .github composer.json package.json .idea .vscode .env .pem; do
	[ ! -e "$STAGE/$NAME/$forbidden" ] || { echo "Must not be in the package: $forbidden" >&2; exit 1; }
done

if [ -n "$(find "$STAGE/$NAME" -type l)" ]; then
	echo "The package contains a symbolic link." >&2
	exit 1
fi

# Nothing that looks like a secret or a log may be in the package, wherever it sits.
if find "$STAGE/$NAME" \( -name '.env*' -o -name '*.pem' -o -name '*.key' -o -name '*.log' -o -name '.idea' -o -name '.vscode' \) | grep -q .; then
	echo "A secret, key, log or editor file is in the package:" >&2
	find "$STAGE/$NAME" \( -name '.env*' -o -name '*.pem' -o -name '*.key' -o -name '*.log' -o -name '.idea' -o -name '.vscode' \) >&2
	exit 1
fi

# Fixed time stamps and a fixed order of the entries, so the same files always give the same zip.
find "$STAGE/$NAME" -exec touch -t 200001010000 {} +
(cd "$STAGE" && find "$NAME" | LC_ALL=C sort | zip -X -q -@ "$OUT/$NAME.zip")
rm -rf "$STAGE"

if unzip -Z1 "$OUT/$NAME.zip" | grep -v "^$NAME/" >/dev/null; then
	echo "The zip has entries outside $NAME/." >&2
	exit 1
fi

node -e '
const { createHash } = require( "node:crypto" );
const { readFileSync, writeFileSync } = require( "node:fs" );
const zip = process.argv[ 1 ];
const sha = createHash( "sha256" ).update( readFileSync( zip ) ).digest( "hex" );
writeFileSync( zip + ".sha256", sha + "  panelladikes-widgets.zip\n" );
console.log( sha + "  panelladikes-widgets.zip" );
' "$OUT/$NAME.zip"
