/**
 * Loads the embed script into the document of the editor, once.
 *
 * The editor only needs the script when a widget block is on the page, so it
 * is added from here instead of being enqueued for every editor screen. The
 * address comes from the contract and is not configurable.
 */
import contract from '../../contract/embed-contract.v2.json';

export const EMBED_SCRIPT_URL = `${ contract.origin }${ contract.script }`;
const SCRIPT_ID = 'panelladikes-widgets-embed-editor';

const pending = new WeakMap();

/**
 * Make `PanelladikesEmbed` available in a document (the editor canvas or the main admin page).
 *
 * @param {Document} doc Document that holds the preview.
 * @return {Promise<Object>} Resolves with the embed API of that document.
 */
export function loadEmbedScript( doc ) {
	const view = doc.defaultView;
	if ( typeof view?.PanelladikesEmbed?.render === 'function' ) {
		return Promise.resolve( view.PanelladikesEmbed );
	}
	if ( pending.has( doc ) ) {
		return pending.get( doc );
	}
	const promise = new Promise( ( resolve, reject ) => {
		const script = doc.createElement( 'script' );
		script.id = SCRIPT_ID;
		script.src = EMBED_SCRIPT_URL;
		script.async = true;
		script.onload = () => {
			pending.delete( doc );
			if ( typeof view?.PanelladikesEmbed?.render === 'function' ) {
				resolve( view.PanelladikesEmbed );
			} else {
				script.remove();
				reject( new Error( 'The embed script did not load.' ) );
			}
		};
		script.onerror = () => {
			pending.delete( doc );
			script.remove();
			reject( new Error( 'The embed script could not be loaded.' ) );
		};
		doc.head.appendChild( script );
	} );
	pending.set( doc, promise );
	return promise;
}
