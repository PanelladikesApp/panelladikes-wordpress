import { describe, expect, it } from 'vitest';
import {
	suggestUrl,
	suggestRequestInit,
	parseSuggest,
	normalizeQuery,
	SUGGEST_URL,
} from '../lib/suggest';

describe( 'suggestUrl', () => {
	it( 'asks for schools of the chosen level', () => {
		const url = new URL( suggestUrl( ' ιατρική  αθήνα ', 'epal' ) );
		expect( `${ url.origin }${ url.pathname }` ).toBe( SUGGEST_URL );
		expect( url.searchParams.get( 'q' ) ).toBe( 'ιατρική αθήνα' );
		expect( url.searchParams.get( 'sources' ) ).toBe( 'schools' );
		expect( url.searchParams.get( 'limit' ) ).toBe( '6' );
		expect( url.searchParams.get( 'level' ) ).toBe( 'epal' );
	} );
	it( 'uses gel for anything but epal', () => {
		expect(
			new URL( suggestUrl( 'x', 'other' ) ).searchParams.get( 'level' )
		).toBe( 'gel' );
	} );
	it( 'caps the query length', () => {
		expect( normalizeQuery( 'a'.repeat( 200 ) ) ).toHaveLength( 80 );
		expect( normalizeQuery( null ) ).toBe( '' );
	} );
} );

describe( 'parseSuggest', () => {
	it( 'keeps schools with a numeric id and a title', () => {
		expect(
			parseSuggest( {
				items: [
					{ id: 'school:12', title: ' Σχολή Α ', subtitle: 'Αθήνα' },
					{ id: 'school:0', title: 'Zero' },
					{ id: 'university:5', title: 'Not a school' },
					{ id: 'school:7', title: '' },
					{ id: 'school:1234567', title: 'Too long id' },
					{ id: 'school:8', title: 'Χωρίς υπότιτλο' },
					null,
				],
			} )
		).toEqual( [
			{ id: 12, title: 'Σχολή Α', subtitle: 'Αθήνα' },
			{ id: 8, title: 'Χωρίς υπότιτλο', subtitle: '' },
		] );
	} );
	it( 'tolerates bad answers', () => {
		expect( parseSuggest( null ) ).toEqual( [] );
		expect( parseSuggest( {} ) ).toEqual( [] );
		expect( parseSuggest( { items: 'x' } ) ).toEqual( [] );
	} );
} );

describe( 'suggestRequestInit', () => {
	it( 'sends no cookies and no referrer', () => {
		const signal = new AbortController().signal;
		const init = suggestRequestInit( signal );
		expect( init.referrerPolicy ).toBe( 'no-referrer' );
		expect( init.credentials ).toBe( 'omit' );
		expect( init.signal ).toBe( signal );
	} );
} );
