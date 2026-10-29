#!/usr/bin/env node
// Signs a release zip with an Ed25519 private key, prints update.json, and
// verifies an update.json before it is uploaded.
//
// Sign (prints update.json):
//   node scripts/sign-release.mjs --key /path/to/private-key.pem --zip panelladikes-widgets.zip \
//        --version 1.0.1 --expect-sha256 <hex from the release> \
//        [--tested 7.1] [--requires 6.5] [--requires-php 8.2]
//
// Print the public key for includes/updater/class-config.php:
//   node scripts/sign-release.mjs --key /path/to/private-key.pem --print-public
//
// --expect-sha256 is required: take the value from the draft release, so a zip
// that was swapped after the build is not signed. Before signing, the public
// key of the private key must be the PUBLIC_KEY of includes/updater/class-config.php
// (--public-key <base64> replaces it, for keys that are not shipped).
//
// An encrypted key (PKCS#8 PEM protected with a passphrase) is supported: give
// the passphrase in a file with --passphrase-file <path outside this repository>,
// or leave the option out and type it when asked (nothing is shown on screen).
// The passphrase is never printed or stored.
//
// Verify an update.json (no private key needed). The public key is read from
// includes/updater/class-config.php unless --public-key <base64> is given:
//   node scripts/sign-release.mjs --verify update.json [--zip panelladikes-widgets.zip]
//
// The private key is an Ed25519 key in PKCS#8 PEM format (for example from
// `openssl genpkey -algorithm ed25519`). It is read from the path you give and
// must live outside this repository; the script refuses a path inside it.
import { createHash, createPrivateKey, createPublicKey, sign, verify } from 'node:crypto';
import { readFileSync, realpathSync, statSync } from 'node:fs';
import { dirname, relative, resolve, isAbsolute } from 'node:path';
import { fileURLToPath } from 'node:url';

const SLUG = 'panelladikes-widgets';
const REPO = 'PanelladikesApp/panelladikes-wordpress';
const VERSION_RE = /^\d{1,4}\.\d{1,4}\.\d{1,4}$/;
const OPTIONAL_VERSION_RE = /^\d{1,2}(\.\d{1,2}){1,2}$/;
const repoRoot = realpathSync( resolve( dirname( fileURLToPath( import.meta.url ) ), '..' ) );
// Fixed prefix of an Ed25519 public key in SPKI/DER form; the 32 raw key bytes follow.
const SPKI_PREFIX = Buffer.from( '302a300506032b6570032100', 'hex' );

function fail( message ) {
	console.error( message );
	process.exit( 1 );
}

const args = new Map();
const argv = process.argv.slice( 2 );
for ( let i = 0; i < argv.length; i++ ) {
	if ( ! argv[ i ].startsWith( '--' ) ) {
		fail( `Unexpected argument: ${ argv[ i ] }` );
	}
	const name = argv[ i ].slice( 2 );
	const next = argv[ i + 1 ];
	if ( next === undefined || next.startsWith( '--' ) ) {
		args.set( name, true );
	} else {
		args.set( name, next );
		i++;
	}
}

const KNOWN = new Set( [ 'key', 'passphrase-file', 'print-public', 'zip', 'version', 'expect-sha256', 'tested', 'requires', 'requires-php', 'verify', 'public-key' ] );
for ( const name of args.keys() ) {
	if ( name === 'passphrase' ) {
		fail( 'A passphrase must not be given on the command line (it would stay in the shell history). Use --passphrase-file <file outside this repository>, or type it when asked.' );
	}
	if ( ! KNOWN.has( name ) ) {
		fail( `Unknown option --${ name }.` );
	}
}

const sha256Of = ( path ) => createHash( 'sha256' ).update( readFileSync( path ) ).digest( 'hex' );
const messageFor = ( version, sha256 ) => Buffer.from( `${ SLUG }:${ version }:${ sha256 }` );
const packageUrl = ( version ) => `https://github.com/${ REPO }/releases/download/v${ version }/${ SLUG }.zip`;

if ( args.has( 'verify' ) ) {
	verifyFeed();
} else {
	await signRelease();
}

function productionPublicKey() {
	const override = args.get( 'public-key' );
	if ( typeof override === 'string' ) {
		return override;
	}
	const config = readFileSync( resolve( repoRoot, 'includes/updater/class-config.php' ), 'utf8' );
	const key = /const PUBLIC_KEY = '([^']*)';/.exec( config )?.[ 1 ] ?? '';
	if ( key === '' ) {
		fail( 'No public key: PUBLIC_KEY in includes/updater/class-config.php is empty. Pass --public-key <base64> to check against another key.' );
	}
	return key;
}

function verifyFeed() {
	const file = args.get( 'verify' );
	if ( typeof file !== 'string' ) {
		fail( 'Missing the update.json path after --verify.' );
	}
	let feed;
	try {
		feed = JSON.parse( readFileSync( file, 'utf8' ) );
	} catch ( error ) {
		fail( `Could not read ${ file }: ${ error.message }` );
	}

	const problems = [];
	const { slug, version, sha256, signature, package: pkg } = feed;
	if ( slug !== SLUG ) {
		problems.push( `slug must be ${ SLUG }` );
	}
	if ( typeof version !== 'string' || ! VERSION_RE.test( version ) ) {
		problems.push( 'version must look like 1.2.3' );
	}
	if ( typeof sha256 !== 'string' || ! /^[a-f0-9]{64}$/.test( sha256 ) ) {
		problems.push( 'sha256 must be 64 lowercase hex characters' );
	}
	const sig = typeof signature === 'string' ? Buffer.from( signature, 'base64' ) : Buffer.alloc( 0 );
	if ( sig.length !== 64 || sig.toString( 'base64' ) !== signature ) {
		problems.push( 'signature must be a base64 Ed25519 signature (64 bytes)' );
	}
	if ( typeof version === 'string' && pkg !== packageUrl( version ) ) {
		problems.push( `package must be ${ packageUrl( version ) }` );
	}
	for ( const key of [ 'tested', 'requires', 'requires_php' ] ) {
		if ( key in feed && ( typeof feed[ key ] !== 'string' || ! OPTIONAL_VERSION_RE.test( feed[ key ] ) ) ) {
			problems.push( `${ key } is not a version like 7.1` );
		}
	}

	const keyBytes = Buffer.from( productionPublicKey(), 'base64' );
	if ( keyBytes.length !== 32 ) {
		problems.push( 'the public key is not 32 bytes of base64' );
	}
	if ( problems.length === 0 ) {
		const publicKey = createPublicKey( { key: Buffer.concat( [ SPKI_PREFIX, keyBytes ] ), format: 'der', type: 'spki' } );
		if ( ! verify( null, messageFor( version, sha256 ), publicKey, sig ) ) {
			problems.push( 'the signature does not match the public key' );
		}
	}
	const zip = args.get( 'zip' );
	if ( typeof zip === 'string' && typeof sha256 === 'string' && sha256Of( zip ) !== sha256 ) {
		problems.push( 'sha256 does not match the zip file' );
	}

	if ( problems.length ) {
		fail( `Not valid:\n- ${ problems.join( '\n- ' ) }` );
	}
	console.log( `OK: ${ SLUG } ${ version } is correctly signed${ typeof zip === 'string' ? ' and matches the zip' : '' }.` );
}

function outsideRepo( path ) {
	let real;
	try {
		real = realpathSync( resolve( path ) );
	} catch {
		real = resolve( path );
	}
	const rel = relative( repoRoot, real );
	return ! ( rel === '' || ( ! rel.startsWith( '..' ) && ! isAbsolute( rel ) ) );
}

/** Ask for a passphrase on the terminal without echoing it. */
function promptPassphrase() {
	return new Promise( ( done, stop ) => {
		if ( ! process.stdin.isTTY ) {
			stop( new Error( 'The key is encrypted. Give --passphrase-file <path> or run this in a terminal.' ) );
			return;
		}
		process.stderr.write( 'Passphrase of the private key: ' );
		let text = '';
		process.stdin.setRawMode( true );
		process.stdin.resume();
		process.stdin.setEncoding( 'utf8' );
		const onData = ( chunk ) => {
			for ( const char of chunk ) {
				if ( char === '\r' || char === '\n' ) {
					process.stdin.setRawMode( false );
					process.stdin.pause();
					process.stdin.removeListener( 'data', onData );
					process.stderr.write( '\n' );
					done( text );
					return;
				}
				if ( char === '\u0003' ) {
					process.stdin.setRawMode( false );
					process.stderr.write( '\n' );
					process.exit( 130 );
				}
				text = char === '\u007f' ? text.slice( 0, -1 ) : text + char;
			}
		};
		process.stdin.on( 'data', onData );
	} );
}

async function signRelease() {
	const keyPath = args.get( 'key' );
	if ( typeof keyPath !== 'string' ) {
		fail( 'Missing --key <path to the private key>.' );
	}
	if ( ! args.has( 'print-public' ) && typeof args.get( 'expect-sha256' ) !== 'string' ) {
		fail( '--expect-sha256 <64 hex characters> is required: use the SHA-256 shown for the zip in the draft release.' );
	}
	const resolvedKey = resolve( keyPath );
	if ( ! outsideRepo( resolvedKey ) ) {
		fail( 'The private key must be stored outside this repository.' );
	}
	try {
		if ( statSync( resolvedKey ).mode & 0o077 ) {
			console.error( 'Warning: the key file can be read by other users. Restrict it with chmod 600.' );
		}
	} catch {
		// A missing file is reported below.
	}

	let pem;
	try {
		pem = readFileSync( resolvedKey );
	} catch ( error ) {
		fail( `Could not read the private key: ${ error.code ?? 'error' }` );
	}
	let passphrase;
	const passphraseFile = args.get( 'passphrase-file' );
	if ( typeof passphraseFile === 'string' ) {
		if ( ! outsideRepo( passphraseFile ) ) {
			fail( 'The passphrase file must be stored outside this repository.' );
		}
		try {
			passphrase = readFileSync( resolve( passphraseFile ), 'utf8' ).replace( /\r?\n$/, '' );
		} catch ( error ) {
			fail( `Could not read the passphrase file: ${ error.code ?? 'error' }` );
		}
	} else if ( pem.includes( 'ENCRYPTED' ) ) {
		try {
			passphrase = await promptPassphrase();
		} catch ( error ) {
			fail( error.message );
		}
	}
	let privateKey;
	try {
		privateKey = createPrivateKey( { key: pem, format: 'pem', passphrase } );
	} catch ( error ) {
		// The message of the underlying error is not shown: it could mention key material.
		fail( 'Could not read the private key (wrong passphrase, or not an Ed25519 PKCS#8 key).' );
	}
	if ( privateKey.asymmetricKeyType !== 'ed25519' ) {
		fail( 'The key is not an Ed25519 key.' );
	}

	if ( args.has( 'print-public' ) ) {
		const der = createPublicKey( privateKey ).export( { type: 'spki', format: 'der' } );
		console.log( der.subarray( der.length - 32 ).toString( 'base64' ) );
		process.exit( 0 );
	}

	// The key must be the one the plugin trusts: check before anything is signed.
	const derived = createPublicKey( privateKey ).export( { type: 'spki', format: 'der' } );
	if ( derived.subarray( derived.length - 32 ).toString( 'base64' ) !== productionPublicKey() ) {
		fail( 'This private key does not belong to the PUBLIC_KEY of the plugin. Nothing was signed.' );
	}

	const zipPath = args.get( 'zip' );
	const version = args.get( 'version' );
	if ( typeof zipPath !== 'string' || typeof version !== 'string' ) {
		fail( 'Missing --zip <file> or --version <x.y.z>.' );
	}
	if ( ! VERSION_RE.test( version ) ) {
		fail( 'The version must look like 1.0.1.' );
	}

	const sha256 = sha256Of( zipPath );
	const expected = args.get( 'expect-sha256' );
	if ( typeof expected !== 'string' || ! /^[a-fA-F0-9]{64}$/.test( expected.trim() ) ) {
		fail( '--expect-sha256 <64 hex characters> is required: use the SHA-256 shown for the zip in the draft release.' );
	}
	if ( expected.trim().toLowerCase() !== sha256 ) {
		fail( `The zip has sha256 ${ sha256 }, not the expected ${ expected }. Nothing was signed.` );
	}
	const signature = sign( null, messageFor( version, sha256 ), privateKey ).toString( 'base64' );

	const feed = { slug: SLUG, version, package: packageUrl( version ), sha256, signature };
	for ( const [ flag, key ] of [ [ 'tested', 'tested' ], [ 'requires', 'requires' ], [ 'requires-php', 'requires_php' ] ] ) {
		const value = args.get( flag );
		if ( typeof value === 'string' ) {
			if ( ! OPTIONAL_VERSION_RE.test( value ) ) {
				fail( `--${ flag } must be a version like 7.1.` );
			}
			feed[ key ] = value;
		}
	}
	console.log( JSON.stringify( feed, null, 2 ) );
}
