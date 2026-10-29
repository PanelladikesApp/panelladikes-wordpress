import { loadEmbedScript, EMBED_SCRIPT_URL } from '../lib/embed-script';

const scripts = () =>
	[ ...document.querySelectorAll( 'script' ) ].filter( ( s ) =>
		s.src.includes( 'embed.v2' )
	);

describe( 'loadEmbedScript', () => {
	afterEach( () => {
		scripts().forEach( ( s ) => s.remove() );
		delete window.PanelladikesEmbed;
	} );

	it( 'uses the fixed address of the embed script', () => {
		expect( EMBED_SCRIPT_URL ).toBe(
			'https://panelladikes.edu.gr/js/embed.v2.js'
		);
	} );

	it( 'adds the script once for any number of calls', async () => {
		const first = loadEmbedScript( document );
		const second = loadEmbedScript( document );
		expect( first ).toBe( second );
		expect( scripts() ).toHaveLength( 1 );
		expect( scripts()[ 0 ].src ).toBe( EMBED_SCRIPT_URL );
		window.PanelladikesEmbed = { render: jest.fn() };
		scripts()[ 0 ].onload();
		await expect( first ).resolves.toBe( window.PanelladikesEmbed );
	} );

	it( 'does not add the script when it is already there', async () => {
		window.PanelladikesEmbed = { render: jest.fn() };
		await loadEmbedScript( document );
		expect( scripts() ).toHaveLength( 0 );
	} );

	it( 'can try again after a failed load', async () => {
		const failed = loadEmbedScript( document );
		scripts()[ 0 ].onerror();
		await expect( failed ).rejects.toThrow();
		expect( scripts() ).toHaveLength( 0 );
		const retry = loadEmbedScript( document );
		expect( scripts() ).toHaveLength( 1 );
		window.PanelladikesEmbed = { render: jest.fn() };
		scripts()[ 0 ].onload();
		await expect( retry ).resolves.toBeDefined();
	} );
} );
