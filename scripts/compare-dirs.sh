#!/bin/sh
# Compares two unpacked packages: the draft release and our own rebuild.
#
#   sh scripts/compare-dirs.sh <draft folder> <rebuilt folder>
#
# Every file must have the same name and the same SHA-256, except the compiled
# translations in languages/ (*.mo and *.json): the tool that builds them writes
# the time of the build inside, so those are compared by their content instead
# (every message, without the dates and tool names). The files that were
# compared by content are listed.
set -eu

[ $# -eq 2 ] && [ -d "$1" ] && [ -d "$2" ] || { echo "Usage: sh scripts/compare-dirs.sh <draft folder> <rebuilt folder>" >&2; exit 1; }
HERE=$(cd "$(dirname "$0")" && pwd)

fingerprint() {
	(cd "$1" && find . -type f | LC_ALL=C sort | while read -r file; do
		case "$file" in
			./*/languages/*.mo | ./*/languages/*.json)
				printf '%s  %s  (content)\n' "$(node "$HERE/semantic-hash.mjs" "$file")" "$file" ;;
			*)
				printf '%s  %s\n' "$(node -e 'const c=require("node:crypto"),f=require("node:fs");process.stdout.write(c.createHash("sha256").update(f.readFileSync(process.argv[1])).digest("hex"))' "$file")" "$file" ;;
		esac
	done)
}

A=$(mktemp)
B=$(mktemp)
trap 'rm -f "$A" "$B"' EXIT
fingerprint "$1" > "$A"
fingerprint "$2" > "$B"

grep '(content)$' "$A" | sed 's/^[0-9a-f]*  //; s/  (content)$//' | while read -r file; do
	echo "Compared by content (not byte for byte): $file"
done

if diff "$A" "$B" > /dev/null; then
	echo "The draft package matches this checkout: $(wc -l < "$A" | tr -d ' ') files, same names; same SHA-256 except the translation files above, which have the same content."
else
	echo "The draft package DIFFERS from this checkout. Do not sign it." >&2
	diff "$A" "$B" >&2 || true
	exit 1
fi
