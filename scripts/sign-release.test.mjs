// Tests of sign-release.mjs with throw-away keys created in the system temporary folder.
import assert from 'node:assert/strict';
import { generateKeyPairSync } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { mkdtempSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { after, before, test } from 'node:test';
import { fileURLToPath } from 'node:url';

const script = join( dirname( fileURLToPath( import.meta.url ) ), 'sign-release.mjs' );
let dir;
let sha;
const run = ( ...args ) => spawnSync( process.execPath, [ script, ...args ], { encoding: 'utf8', input: '' } );

before( () => {
	dir = mkdtempSync( join( tmpdir(), 'pw-sign-test-' ) );
	const pem = ( passphrase ) =>
		generateKeyPairSync( 'ed25519' ).privateKey.export( {
			type: 'pkcs8',
			format: 'pem',
			...( passphrase ? { cipher: 'aes-256-cbc', passphrase } : {} ),
		} );
	writeFileSync( join( dir, 'plain.pem' ), pem(), { mode: 0o600 } );
	writeFileSync( join( dir, 'enc.pem' ), pem( 'test-only-passphrase' ), { mode: 0o600 } );
	writeFileSync( join( dir, 'open.pem' ), pem(), { mode: 0o644 } );
	writeFileSync( join( dir, 'pass.txt' ), 'test-only-passphrase\n' );
	writeFileSync( join( dir, 'wrong.txt' ), 'not-the-passphrase\n' );
	writeFileSync( join( dir, 'p.zip' ), 'zip content for the test' );
	sha = createHash( 'sha256' ).update( 'zip content for the test' ).digest( 'hex' );
	symlinkSync( script, join( dir, 'link-to-repo-file.pem' ) );
} );
after( () => rmSync( dir, { recursive: true, force: true } ) );

test( 'signs with an encrypted key given a passphrase file, and the result verifies', () => {
	const pub = run( '--key', join( dir, 'enc.pem' ), '--passphrase-file', join( dir, 'pass.txt' ), '--print-public' );
	assert.equal( pub.status, 0 );
	const signed = run( '--key', join( dir, 'enc.pem' ), '--passphrase-file', join( dir, 'pass.txt' ), '--zip', join( dir, 'p.zip' ), '--version', '1.0.1', '--expect-sha256', sha, '--public-key', pub.stdout.trim() );
	assert.equal( signed.status, 0 );
	writeFileSync( join( dir, 'update.json' ), signed.stdout );
	const check = run( '--verify', join( dir, 'update.json' ), '--zip', join( dir, 'p.zip' ), '--public-key', pub.stdout.trim() );
	assert.equal( check.status, 0, check.stderr );
} );

test( 'an unencrypted key still works', () => {
	assert.equal( run( '--key', join( dir, 'plain.pem' ), '--print-public' ).status, 0 );
} );

test( 'refuses an encrypted key without a passphrase when there is no terminal', () => {
	const result = run( '--key', join( dir, 'enc.pem' ), '--print-public' );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /encrypted/ );
} );

test( 'a wrong passphrase fails without echoing any secret', () => {
	const result = run( '--key', join( dir, 'enc.pem' ), '--passphrase-file', join( dir, 'wrong.txt' ), '--print-public' );
	assert.equal( result.status, 1 );
	assert.doesNotMatch( result.stdout + result.stderr, /not-the-passphrase|test-only-passphrase|PRIVATE KEY/ );
} );

test( 'refuses a passphrase file or a key inside the repository', () => {
	assert.equal( run( '--key', join( dir, 'enc.pem' ), '--passphrase-file', script, '--print-public' ).status, 1 );
	assert.equal( run( '--key', script, '--print-public' ).status, 1 );
} );

const signArgs = ( key, ...more ) => [ '--key', join( dir, key ), '--zip', join( dir, 'p.zip' ), '--version', '1.0.1', ...more ];

test( '--expect-sha256 is required', () => {
	const pub = run( '--key', join( dir, 'plain.pem' ), '--print-public' ).stdout.trim();
	const result = run( ...signArgs( 'plain.pem', '--public-key', pub ) );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /--expect-sha256/ );
	assert.equal( result.stdout, '' );
} );

test( 'a wrong --expect-sha256 signs nothing', () => {
	const pub = run( '--key', join( dir, 'plain.pem' ), '--print-public' ).stdout.trim();
	const result = run( ...signArgs( 'plain.pem', '--expect-sha256', 'a'.repeat( 64 ), '--public-key', pub ) );
	assert.equal( result.status, 1 );
	assert.equal( result.stdout, '' );
} );

test( 'a key that is not the PUBLIC_KEY of the plugin is refused before signing, without printing key material', () => {
	const result = run( ...signArgs( 'plain.pem', '--expect-sha256', sha ) );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /does not belong to the PUBLIC_KEY/ );
	assert.equal( result.stdout, '' );
	assert.doesNotMatch( result.stderr, /[A-Za-z0-9+/]{40,}/ );
	const other = run( ...signArgs( 'plain.pem', '--expect-sha256', sha, '--public-key', Buffer.alloc( 32, 1 ).toString( 'base64' ) ) );
	assert.equal( other.status, 1 );
} );

test( 'unknown options are refused, and a passphrase on the command line points to --passphrase-file', () => {
	const unknown = run( '--key', join( dir, 'plain.pem' ), '--print-public', '--surprise', 'x' );
	assert.equal( unknown.status, 1 );
	assert.match( unknown.stderr, /Unknown option --surprise/ );
	const secret = run( '--key', join( dir, 'enc.pem' ), '--passphrase', 'hunter2', '--print-public' );
	assert.equal( secret.status, 1 );
	assert.match( secret.stderr, /--passphrase-file/ );
	assert.doesNotMatch( secret.stdout + secret.stderr, /hunter2/ );
} );

test( 'a key file readable by others gives a warning, a private one does not', () => {
	assert.match( run( '--key', join( dir, 'open.pem' ), '--print-public' ).stderr, /chmod 600/ );
	assert.doesNotMatch( run( '--key', join( dir, 'plain.pem' ), '--print-public' ).stderr, /chmod 600/ );
} );

test( 'a link to a file of the repository does not get around the outside-the-repository rule', () => {
	const result = run( '--key', join( dir, 'link-to-repo-file.pem' ), '--print-public' );
	assert.equal( result.status, 1 );
	assert.match( result.stderr, /outside this repository/ );
} );
