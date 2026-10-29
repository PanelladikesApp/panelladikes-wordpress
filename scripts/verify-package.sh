#!/bin/sh
# Rebuilds the release package from this checkout and compares it, file by file,
# with the zip of a draft release. Run it before signing.
#
#   sh scripts/verify-package.sh /path/to/downloaded/panelladikes-widgets.zip
#
# Use a clean checkout of the release tag. The build is done with `npm ci
# --ignore-scripts`; the translations in languages/ are rebuilt only when
# WP_CLI_PHAR points to a WP-CLI phar (as in the release workflow), otherwise
# the committed ones are used. Set ALLOW_DIRTY=1 to skip the clean-checkout check.
#
# The zip files themselves can differ byte for byte (time stamps), so the
# comparison is of the file lists and of the SHA-256 of every file.
set -eu

[ $# -eq 1 ] && [ -f "$1" ] || { echo "Usage: sh scripts/verify-package.sh <panelladikes-widgets.zip of the draft>" >&2; exit 1; }
cd "$(dirname "$0")/.."
DRAFT=$(cd "$(dirname "$1")" && pwd)/$(basename "$1")

if [ "${ALLOW_DIRTY:-}" != "1" ] && [ -n "$(git status --porcelain)" ]; then
	echo "The working tree is not clean. Check out the release tag first (or set ALLOW_DIRTY=1)." >&2
	exit 1
fi

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

# Before anything is built or unpacked: the draft must be plain files and folders, with no repeated names.
sh scripts/check-zip.sh "$DRAFT"

npm ci --ignore-scripts
npm run build
if [ -n "${WP_CLI_PHAR:-}" ]; then
	WP_CLI_PHAR="$WP_CLI_PHAR" sh scripts/make-translations.sh
fi
sh scripts/build-package.sh "$WORK/rebuilt"

# The complete list of entries (folders included) must be the same.
sh scripts/check-zip.sh "$DRAFT" "$WORK/rebuilt/panelladikes-widgets.zip"

mkdir "$WORK/draft" "$WORK/mine"
unzip -q "$DRAFT" -d "$WORK/draft"
unzip -q "$WORK/rebuilt/panelladikes-widgets.zip" -d "$WORK/mine"

listing() {
	(cd "$1" && find . -type f | sort | while read -r file; do
		printf '%s  %s\n' "$(node -e 'const c=require("node:crypto"),f=require("node:fs");process.stdout.write(c.createHash("sha256").update(f.readFileSync(process.argv[1])).digest("hex"))' "$file")" "$file"
	done)
}
listing "$WORK/draft" > "$WORK/draft.txt"
listing "$WORK/mine" > "$WORK/mine.txt"

if diff "$WORK/draft.txt" "$WORK/mine.txt" > "$WORK/diff.txt"; then
	echo "The draft package matches this checkout: $(wc -l < "$WORK/draft.txt" | tr -d ' ') files, same names and same SHA-256."
	echo "Zip SHA-256 of the draft: $(node -e 'const c=require("node:crypto"),f=require("node:fs");process.stdout.write(c.createHash("sha256").update(f.readFileSync(process.argv[1])).digest("hex"))' "$DRAFT")"
else
	echo "The draft package DIFFERS from this checkout. Do not sign it." >&2
	cat "$WORK/diff.txt" >&2
	exit 1
fi
