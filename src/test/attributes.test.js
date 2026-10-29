import blockJson from '../block.json';
import contract from '../../contract/embed-contract.v2.json';
import {
	normalize,
	dataAttributes,
	normalizeWidth,
	widthMode,
	heightMode,
} from '../lib/attributes';

const html = ( raw ) => {
	const set = normalize( raw );
	return set
		? dataAttributes( set )
				.map( ( [ k, v ] ) => `${ k }="${ v }"` )
				.join( ' ' )
		: '';
};

describe( 'block.json and the contract', () => {
	const attrs = blockJson.attributes;
	it( 'allow the same values', () => {
		expect( attrs.type.enum ).toEqual( contract.types );
		for ( const name of [ 'edu', 'theme', 'align', 'shadow' ] ) {
			expect( attrs[ name ].enum ).toEqual(
				contract.attributes[ name ].enum
			);
			expect( attrs[ name ].default ).toBe(
				contract.attributes[ name ].default
			);
		}
	} );
	it( 'use the contract defaults', () => {
		expect( attrs.radius.default ).toBe(
			contract.attributes.radius.default
		);
		expect( attrs.border.default ).toBe(
			contract.attributes.border.default
		);
		expect( attrs.lazy.default ).toBe( contract.attributes.lazy.default );
	} );
} );

describe( 'normalize and dataAttributes', () => {
	it( 'prints the defaults of a fresh block', () => {
		const defaults = Object.fromEntries(
			Object.entries( blockJson.attributes ).map( ( [ k, v ] ) => [
				k,
				v.default,
			] )
		);
		expect( html( defaults ) ).toBe(
			'data-panelladikes-widget="calculator" data-edu="gel" data-theme="auto" data-height="720" data-radius="12"'
		);
	} );
	it( 'keeps the order of the server', () => {
		expect(
			html( {
				type: 'program',
				edu: 'gel',
				theme: 'dark',
				pedio: 3,
				width: '600',
				align: 'center',
				height: '800',
				border: true,
				shadow: 'lg',
				radius: 8,
				lazy: false,
			} )
		).toBe(
			'data-panelladikes-widget="program" data-edu="gel" data-theme="dark" data-pedio="3" data-width="600" data-align="center" data-height="800" data-border="1" data-shadow="lg" data-radius="8" data-lazy="false"'
		);
	} );
	it( 'ignores pedio for epal', () => {
		expect(
			html( { type: 'program', edu: 'epal', pedio: 2 } )
		).not.toContain( 'pedio' );
		expect( html( { type: 'program', edu: 'gel', pedio: 2 } ) ).toContain(
			'data-pedio="2"'
		);
		expect(
			normalize( { type: 'program', edu: 'epal', pedio: 2 } ).pedio
		).toBeNull();
	} );
	it( 'maps schoolId to data-id and needs it for sxoli', () => {
		expect( html( { type: 'sxoli', schoolId: 77 } ) ).toContain(
			'data-id="77"'
		);
		expect( html( { type: 'sxoli', schoolId: 0 } ) ).toBe( '' );
		expect( html( { type: 'sxoli' } ) ).toBe( '' );
		expect( html( { type: 'sxoli', schoolId: 1000000 } ) ).toBe( '' );
		expect( html( { type: 'baseis', schoolId: 5 } ) ).not.toContain(
			'data-id'
		);
	} );
	it( 'rejects unknown types and falls back on bad values', () => {
		expect( normalize( { type: 'nope' } ) ).toBeNull();
		expect( normalize( {} ) ).toBeNull();
		expect(
			html( {
				type: 'baseis',
				edu: 'x',
				theme: 'x',
				align: 'x',
				shadow: 'x',
				radius: 49,
				pedio: 5,
				height: '199',
				width: '50px',
				border: 'maybe',
				lazy: 'maybe',
			} )
		).toBe(
			'data-panelladikes-widget="baseis" data-edu="gel" data-theme="auto" data-radius="12"'
		);
	} );
	it( 'applies limits inclusively', () => {
		expect(
			html( {
				type: 'baseis',
				pedio: 1,
				radius: 0,
				height: 200,
				width: 200,
			} )
		).toBe(
			'data-panelladikes-widget="baseis" data-edu="gel" data-theme="auto" data-pedio="1" data-width="200" data-height="200"'
		);
		expect(
			html( {
				type: 'baseis',
				pedio: 4,
				radius: 48,
				height: 10000,
				width: '2000px',
			} )
		).toContain( 'data-width="2000"' );
		expect(
			html( { type: 'baseis', pedio: 0, height: 10001, width: 2001 } )
		).not.toMatch( /pedio|height|width/ );
	} );
	it( 'handles height auto and the align rule', () => {
		expect( html( { type: 'calculator', height: 'auto' } ) ).not.toContain(
			'data-height'
		);
		expect( html( { type: 'baseis', align: 'right' } ) ).not.toContain(
			'data-align'
		);
		expect(
			html( { type: 'baseis', align: 'right', width: 400 } )
		).toContain( 'data-align="right"' );
	} );
	it( 'never lets markup through', () => {
		const evil = '"><script>alert(1)</script>';
		const out = html( {
			type: 'baseis',
			edu: evil,
			theme: evil,
			width: evil,
			height: evil,
			radius: evil,
			shadow: evil,
			align: evil,
		} );
		expect( out ).toBe(
			'data-panelladikes-widget="baseis" data-edu="gel" data-theme="auto" data-radius="12"'
		);
		expect( html( { type: evil } ) ).toBe( '' );
	} );
} );

describe( 'width and height modes', () => {
	it( 'normalizes widths', () => {
		expect( normalizeWidth( '' ) ).toBeNull();
		expect( normalizeWidth( '100%' ) ).toBeNull();
		expect( normalizeWidth( '50%' ) ).toBe( '50%' );
		expect( normalizeWidth( '9%' ) ).toBeNull();
		expect( normalizeWidth( '600px' ) ).toBe( '600' );
	} );
	it( 'reports the editing mode', () => {
		expect( widthMode( '' ) ).toBe( 'full' );
		expect( widthMode( '600' ) ).toBe( 'px' );
		expect( widthMode( '40%' ) ).toBe( 'percent' );
		expect( heightMode( '' ) ).toBe( 'default' );
		expect( heightMode( 'auto' ) ).toBe( 'auto' );
		expect( heightMode( '800' ) ).toBe( 'fixed' );
	} );
} );
