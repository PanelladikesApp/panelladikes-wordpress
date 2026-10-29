#!/usr/bin/env node
// Checks that a release tag (for example v1.0.1) matches the version written in
// the plugin header, the version constant, block.json, package.json and readme.txt.
//
//   node scripts/check-version.mjs v1.0.1
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const read = ( path ) => readFileSync( join( root, path ), 'utf8' );
const capture = ( path, pattern ) => pattern.exec( read( path ) )?.[ 1 ] ?? null;

const tag = process.argv[ 2 ] ?? '';
const match = /^v(\d{1,4}\.\d{1,4}\.\d{1,4})$/.exec( tag );
if ( ! match ) {
	console.error( `The tag must look like v1.2.3, got "${ tag }".` );
	process.exit( 1 );
}
const expected = match[ 1 ];

const found = {
	'panelladikes-widgets.php (header)': capture( 'panelladikes-widgets.php', /^ \* Version:\s+(\S+)$/m ),
	'panelladikes-widgets.php (VERSION)': capture( 'panelladikes-widgets.php', /const VERSION\s+=\s+'([^']+)'/ ),
	'src/block.json': JSON.parse( read( 'src/block.json' ) ).version ?? null,
	'package.json': JSON.parse( read( 'package.json' ) ).version ?? null,
	'readme.txt (Stable tag)': capture( 'readme.txt', /^Stable tag:\s+(\S+)$/m ),
};

let failed = false;
for ( const [ where, version ] of Object.entries( found ) ) {
	if ( version !== expected ) {
		console.error( `${ where }: ${ version } (expected ${ expected })` );
		failed = true;
	}
}
if ( failed ) {
	process.exit( 1 );
}
console.log( `Version ${ expected } matches the tag in ${ Object.keys( found ).length } places.` );
