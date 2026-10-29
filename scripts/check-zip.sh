#!/bin/sh
# Structural checks of a release zip.
#
#   sh scripts/check-zip.sh <zip> [<reference zip>]
#
# Fails when the zip has an entry that is neither a regular file nor a folder
# (for example a symbolic link), when a name appears twice, or, if a reference
# zip is given, when the complete list of entries differs from it.
set -eu

[ $# -ge 1 ] && [ -f "$1" ] || { echo "Usage: sh scripts/check-zip.sh <zip> [<reference zip>]" >&2; exit 1; }
ZIP=$1
status=0

# In the long listing every entry starts with its type: "-" regular file, "d" folder, anything else is refused.
odd=$(unzip -Z "$ZIP" | grep -E '^[-dlcbpsD?][-r][-w]' | grep -v -E '^[-d]' || true)
if [ -n "$odd" ]; then
	echo "The zip has entries that are not regular files or folders:" >&2
	echo "$odd" >&2
	status=1
fi

dups=$(unzip -Z1 "$ZIP" | sort | uniq -d)
if [ -n "$dups" ]; then
	echo "The zip has names that appear more than once:" >&2
	echo "$dups" >&2
	status=1
fi

if [ $# -ge 2 ]; then
	A=$(mktemp)
	B=$(mktemp)
	unzip -Z1 "$ZIP" | LC_ALL=C sort > "$A"
	unzip -Z1 "$2" | LC_ALL=C sort > "$B"
	if ! diff "$A" "$B" > /dev/null; then
		echo "The list of entries differs from the reference zip:" >&2
		diff "$A" "$B" >&2 || true
		status=1
	fi
	rm -f "$A" "$B"
fi

exit $status
