// Tests of compare-dirs.sh and semantic-hash.mjs with small packages made in the system temporary folder.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { cpSync, mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { after, before, test } from 'node:test';
import { fileURLToPath } from 'node:url';

const scripts = dirname( fileURLToPath( import.meta.url ) );
let dir;

/** Write a GNU gettext .mo file (little endian) from [original, translation] pairs. */
function mo( pairs ) {
	const sorted = [ ...pairs ].sort( ( a, b ) => ( a[ 0 ] < b[ 0 ] ? -1 : 1 ) );
	const n = sorted.length;
	const head = 28;
	const originals = head;
	const translations = head + n * 8;
	let offset = head + n * 16;
	const strings = [];
	const table = ( index ) =>
		sorted.map( ( pair ) => {
			const bytes = Buffer.from( pair[ index ], 'utf8' );
			const entry = [ bytes.length, offset ];
			offset += bytes.length + 1;
			strings.push( Buffer.concat( [ bytes, Buffer.from( [ 0 ] ) ] ) );
			return entry;
		} );
	const o = table( 0 );
	const t = table( 1 );
	const buffer = Buffer.alloc( head + n * 16 );
	[ 0x950412de, 0, n, originals, translations, 0, 0 ].forEach( ( value, i ) => buffer.writeUInt32LE( value, i * 4 ) );
	o.forEach( ( [ length, off ], i ) => {
		buffer.writeUInt32LE( length, originals + i * 8 );
		buffer.writeUInt32LE( off, originals + i * 8 + 4 );
	} );
	t.forEach( ( [ length, off ], i ) => {
		buffer.writeUInt32LE( length, translations + i * 8 );
		buffer.writeUInt32LE( off, translations + i * 8 + 4 );
	} );
	return Buffer.concat( [ buffer, ...strings ] );
}

const header = ( date, who ) =>
	[ 'Project-Id-Version: Panelladikes Widgets 1.0.0', `POT-Creation-Date: ${ date }`, `PO-Revision-Date: ${ date }`, `Last-Translator: ${ who }`, 'Language-Team: x', 'Language: el', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Plural-Forms: nplurals=2; plural=(n != 1);' ].join( '\n' ) + '\n';

const json = ( date, strings ) =>
	JSON.stringify( { 'translation-revision-date': date, generator: 'WP-CLI/2.12.0', source: 'build/index.js', domain: 'messages', locale_data: { messages: { '': { domain: 'messages', lang: 'el' }, ...strings } } } );

/** Make a package folder; `variant` changes only what the test needs. */
function makePackage( name, { date = '2026-10-29 10:00+0000', who = 'A', msgstr = 'Γειά', jsonDate = '2026-10-29T10:00:00+00:00', jsonString = 'Γειά', php = '<?php' } = {} ) {
	const root = join( dir, name, 'panelladikes-widgets' );
	mkdirSync( join( root, 'languages' ), { recursive: true } );
	writeFileSync( join( root, 'a.php' ), php );
	writeFileSync( join( root, 'languages', 'x-el.mo' ), mo( [ [ '', header( date, who ) ], [ 'Hello', msgstr ], [ 'Bye', 'Αντίο' ] ] ) );
	writeFileSync( join( root, 'languages', 'x-el-abc.json' ), json( jsonDate, { Hello: [ jsonString ] } ) );
	return join( dir, name );
}
const compare = ( a, b ) => spawnSync( 'sh', [ join( scripts, 'compare-dirs.sh' ), a, b ], { encoding: 'utf8' } );

before( () => {
	dir = mkdtempSync( join( tmpdir(), 'pw-compare-test-' ) );
} );
after( () => rmSync( dir, { recursive: true, force: true } ) );

test( 'identical packages match', () => {
	const result = compare( makePackage( 'a1' ), makePackage( 'b1' ) );
	assert.equal( result.status, 0, result.stderr );
	assert.match( result.stdout, /matches/ );
} );

test( 'only the dates and tool names of the translations differ: matches, and says which files were compared by content', () => {
	const result = compare(
		makePackage( 'a2' ),
		makePackage( 'b2', { date: '2030-01-01 00:00+0000', who: 'Somebody else', jsonDate: '2030-01-01T00:00:00+00:00' } )
	);
	assert.equal( result.status, 0, result.stderr );
	assert.match( result.stdout, /Compared by content.*x-el\.mo/ );
	assert.match( result.stdout, /Compared by content.*x-el-abc\.json/ );
} );

test( 'a changed message in the .mo differs', () => {
	const result = compare( makePackage( 'a3' ), makePackage( 'b3', { msgstr: 'Αλλαγμένο' } ) );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /DIFFERS/ );
} );

test( 'a changed string in the JSON differs', () => {
	const result = compare( makePackage( 'a4' ), makePackage( 'b4', { jsonString: 'Αλλαγμένο' } ) );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /DIFFERS/ );
} );

test( 'a changed file that is not a translation differs, even if the translations are the same', () => {
	const result = compare( makePackage( 'a5' ), makePackage( 'b5', { php: '<?php // injected' } ) );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /a\.php/ );
} );

test( 'a missing or extra file differs', () => {
	const a = makePackage( 'a6' );
	const b = makePackage( 'b6' );
	writeFileSync( join( b, 'panelladikes-widgets', 'extra.php' ), '<?php' );
	assert.equal( compare( a, b ).status, 1 );
} );

test( 'a header line that matters (not a date) differs', () => {
	const a = makePackage( 'a7' );
	const b = makePackage( 'b7' );
	cpSync( join( b, 'panelladikes-widgets', 'languages', 'x-el.mo' ), join( dir, 'keep.mo' ) );
	writeFileSync( join( b, 'panelladikes-widgets', 'languages', 'x-el.mo' ), mo( [ [ '', header( '2026-10-29', 'A' ).replace( 'nplurals=2', 'nplurals=3' ) ], [ 'Hello', 'Γειά' ], [ 'Bye', 'Αντίο' ] ] ) );
	assert.equal( compare( a, b ).status, 1 );
} );
