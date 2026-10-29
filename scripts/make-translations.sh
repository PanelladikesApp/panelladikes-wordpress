#!/bin/sh
# Compiles languages/panelladikes-widgets-el.po into the .mo (PHP strings) and
# the JSON file (strings of the editor script).
#
# Run `npm run build` first: the JSON file name is derived from the path of the
# built script, build/index.js.
#
# Two ways to run it:
#  - with WP_CLI_PHAR=/path/to/wp-cli.phar and PHP: no WordPress needed
#    (this is what the release workflow does);
#  - without it: needs `msgfmt` (gettext) and a running wp-env
#    (`npx wp-env start`), whose WP-CLI provides `wp i18n make-json`.
#
# The existing files are only replaced once the new ones have been produced, so
# a failed run leaves languages/ as it was.
set -eu

cd "$(dirname "$0")/.."
PO=languages/panelladikes-widgets-el.po
MO=languages/panelladikes-widgets-el.mo
DIR=$(basename "$PWD")
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

sed -E "s#^\#: src/[A-Za-z-]+\.js#\#: build/index.js#" "$PO" > "$TMP/panelladikes-widgets-el.po"

if [ -n "${WP_CLI_PHAR:-}" ]; then
	php "$WP_CLI_PHAR" i18n make-json "$TMP" --no-purge
	php "$WP_CLI_PHAR" i18n make-mo "$TMP"
else
	msgfmt --check -o "$TMP/panelladikes-widgets-el.mo" "$PO"
	# wp-env writes into a staging folder inside the plugin, which is removed afterwards.
	STAGE=languages/.make-translations
	rm -rf "$STAGE"
	mkdir "$STAGE"
	trap 'rm -rf "$TMP" "$STAGE"' EXIT
	npx wp-env run cli bash -c '
set -e
cd /var/www/html/wp-content/plugins/'"$DIR"'
rm -rf /tmp/mj && mkdir /tmp/mj
sed -E "s#^\#: src/[A-Za-z-]+\.js#\#: build/index.js#" languages/panelladikes-widgets-el.po > /tmp/mj/panelladikes-widgets-el.po
wp i18n make-json /tmp/mj --no-purge
cp /tmp/mj/*.json '"$STAGE"'/
'
	cp "$STAGE"/*.json "$TMP"/
fi

# Everything was produced: only now replace the existing files.
ls "$TMP"/panelladikes-widgets-el-*.json >/dev/null
[ -s "$TMP/panelladikes-widgets-el.mo" ]
rm -f languages/panelladikes-widgets-el-*.json
cp "$TMP"/panelladikes-widgets-el-*.json languages/
cp "$TMP/panelladikes-widgets-el.mo" "$MO"
ls languages
