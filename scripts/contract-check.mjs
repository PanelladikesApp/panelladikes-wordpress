#!/usr/bin/env node
// Verifies that contract/embed-contract.v2.json is the file whose sha256 is recorded
// in contract/embed-contract.v2.sha256, and that it is well-formed JSON.
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const dir = join( dirname( fileURLToPath( import.meta.url ) ), '..', 'contract' );
const file = readFileSync( join( dir, 'embed-contract.v2.json' ) );
const expected = readFileSync( join( dir, 'embed-contract.v2.sha256' ), 'utf8' ).trim().split( /\s+/ )[ 0 ];
const actual = createHash( 'sha256' ).update( file ).digest( 'hex' );

try {
	JSON.parse( file.toString( 'utf8' ) );
} catch ( error ) {
	console.error( `contract:check failed, the contract is not valid JSON: ${ error.message }` );
	process.exit( 1 );
}

if ( actual !== expected ) {
	console.error( `contract:check failed.\n  expected ${ expected }\n  actual   ${ actual }` );
	process.exit( 1 );
}
console.log( `contract:check ok (${ actual })` );
