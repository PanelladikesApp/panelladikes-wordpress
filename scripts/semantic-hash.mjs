#!/usr/bin/env node
// Prints a hash of the CONTENT of a translation file, ignoring what changes
// every time the file is rebuilt (dates and tool names).
//
//   node scripts/semantic-hash.mjs languages/panelladikes-widgets-el.mo
//   node scripts/semantic-hash.mjs languages/panelladikes-widgets-el-<hash>.json
//
// .mo   : every message (context, original, translation, plural forms) and the
//         header without POT-Creation-Date, PO-Revision-Date, Last-Translator,
//         Language-Team and X-Generator; the order of the header lines does not matter.
// .json : the whole file without `translation-revision-date` and `generator`.
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { extname } from 'node:path';
import { fileURLToPath } from 'node:url';

const VOLATILE_HEADERS = new Set( [ 'pot-creation-date', 'po-revision-date', 'last-translator', 'language-team', 'x-generator' ] );

/**
 * Parse a GNU gettext .mo file.
 *
 * @param {Buffer} buffer File content.
 * @return {Array<[string, string]>} [original, translation] pairs, in file order.
 */
export function parseMo( buffer ) {
	const magic = buffer.readUInt32LE( 0 );
	let little;
	if ( magic === 0x950412de ) {
		little = true;
	} else if ( magic === 0xde120495 ) {
		little = false;
	} else {
		throw new Error( 'Not a .mo file.' );
	}
	const u32 = ( offset ) => ( little ? buffer.readUInt32LE( offset ) : buffer.readUInt32BE( offset ) );
	const count = u32( 8 );
	const originals = u32( 12 );
	const translations = u32( 16 );
	const text = ( table, index ) => {
		const length = u32( table + index * 8 );
		const offset = u32( table + index * 8 + 4 );
		return buffer.toString( 'utf8', offset, offset + length );
	};
	const out = [];
	for ( let i = 0; i < count; i++ ) {
		out.push( [ text( originals, i ), text( translations, i ) ] );
	}
	return out;
}

/** Canonical text of the content of a .mo file. */
export function moContent( buffer ) {
	const messages = [];
	let header = [];
	for ( const [ original, translation ] of parseMo( buffer ) ) {
		if ( original === '' ) {
			header = translation
				.split( '\n' )
				.filter( ( line ) => line.trim() !== '' )
				.filter( ( line ) => ! VOLATILE_HEADERS.has( line.split( ':' )[ 0 ].trim().toLowerCase() ) )
				.sort();
		} else {
			messages.push( [ original, translation ] );
		}
	}
	messages.sort( ( a, b ) => ( a[ 0 ] < b[ 0 ] ? -1 : a[ 0 ] > b[ 0 ] ? 1 : 0 ) );
	return JSON.stringify( { header, messages } );
}

const sortKeys = ( value ) => {
	if ( Array.isArray( value ) ) {
		return value.map( sortKeys );
	}
	if ( value && typeof value === 'object' ) {
		return Object.fromEntries( Object.keys( value ).sort().map( ( key ) => [ key, sortKeys( value[ key ] ) ] ) );
	}
	return value;
};

/** Canonical text of the content of a translation JSON file. */
export function jsonContent( buffer ) {
	const data = JSON.parse( buffer.toString( 'utf8' ) );
	delete data[ 'translation-revision-date' ];
	delete data.generator;
	return JSON.stringify( sortKeys( data ) );
}

export function semanticHash( path ) {
	const buffer = readFileSync( path );
	const extension = extname( path );
	let content;
	if ( extension === '.mo' ) {
		content = moContent( buffer );
	} else if ( extension === '.json' ) {
		content = jsonContent( buffer );
	} else {
		throw new Error( 'Only .mo and .json files can be compared by content.' );
	}
	return createHash( 'sha256' ).update( content ).digest( 'hex' );
}

if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	try {
		console.log( semanticHash( process.argv[ 2 ] ) );
	} catch ( error ) {
		console.error( `Could not read ${ process.argv[ 2 ] }: ${ error.message }` );
		process.exit( 1 );
	}
}
