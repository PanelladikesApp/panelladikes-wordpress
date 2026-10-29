// Tests of check-zip.sh with small zip files made in the system temporary folder.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { after, before, test } from 'node:test';
import { fileURLToPath } from 'node:url';

const script = join( dirname( fileURLToPath( import.meta.url ) ), 'check-zip.sh' );
let dir;
const check = ( ...zips ) => spawnSync( 'sh', [ script, ...zips ], { encoding: 'utf8' } );
const zip = ( cwd, name, args ) => {
	const result = spawnSync( 'zip', [ '-q', '-X', ...args, name, '-r', 'panelladikes-widgets' ], { cwd } );
	assert.equal( result.status, 0 );
	return join( cwd, name );
};

before( () => {
	dir = mkdtempSync( join( tmpdir(), 'pw-zip-test-' ) );
	for ( const sub of [ 'good', 'link', 'dup' ] ) {
		mkdirSync( join( dir, sub, 'panelladikes-widgets' ), { recursive: true } );
		writeFileSync( join( dir, sub, 'panelladikes-widgets', 'a.php' ), '<?php' );
	}
	symlinkSync( '/etc/hosts', join( dir, 'link', 'panelladikes-widgets', 'b.php' ) );
} );
after( () => rmSync( dir, { recursive: true, force: true } ) );

test( 'a zip of regular files passes', () => {
	assert.equal( check( zip( join( dir, 'good' ), 'good.zip', [] ) ).status, 0 );
} );

test( 'a zip with a symbolic link fails', () => {
	const result = check( zip( join( dir, 'link' ), 'link.zip', [ '-y' ] ) );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /not regular files/ );
} );

test( 'a zip with a repeated name fails', () => {
	const cwd = join( dir, 'dup' );
	const first = zip( cwd, 'dup.zip', [] );
	// Add the same path a second time: zip -g replaces, so write the duplicate entry with Python.
	const py = spawnSync(
		'python3',
		[ '-c', "import zipfile,sys,warnings; warnings.simplefilter('ignore'); z=zipfile.ZipFile(sys.argv[1],'a'); z.writestr('panelladikes-widgets/a.php','<?php second'); z.close()", first ],
		{ encoding: 'utf8' }
	);
	assert.equal( py.status, 0, py.stderr );
	const result = check( first );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /more than once/ );
} );

test( 'the list of entries is compared with a reference zip', () => {
	const good = join( dir, 'good', 'good.zip' );
	assert.equal( check( good, good ).status, 0 );
	writeFileSync( join( dir, 'good', 'panelladikes-widgets', 'extra.php' ), '<?php' );
	const other = zip( join( dir, 'good' ), 'other.zip', [] );
	const result = check( other, good );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /differs from the reference/ );
} );
