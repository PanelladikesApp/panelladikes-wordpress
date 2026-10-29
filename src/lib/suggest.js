/**
 * School search used by the editor.
 *
 * The address of the search service is written here and nowhere else.
 */
export const SUGGEST_URL = 'https://search-paneladikesapp.web.app/v1/suggest';
export const MIN_QUERY_LENGTH = 2;
export const MAX_QUERY_LENGTH = 80;
export const RESULT_LIMIT = 6;
export const DEBOUNCE_MS = 300;

/**
 * Collapse whitespace and cap the length of a search text.
 *
 * @param {*} query Raw text.
 * @return {string} Clean text.
 */
export const normalizeQuery = ( query ) =>
	String( query ?? '' )
		.replace( /\s+/g, ' ' )
		.trim()
		.slice( 0, MAX_QUERY_LENGTH );

/**
 * Request address for a school search.
 *
 * @param {string} query Search text.
 * @param {string} edu   "gel" or "epal".
 * @return {string} URL.
 */
export function suggestUrl( query, edu ) {
	const url = new URL( SUGGEST_URL );
	url.searchParams.set( 'q', normalizeQuery( query ) );
	url.searchParams.set( 'sources', 'schools' );
	url.searchParams.set( 'limit', String( RESULT_LIMIT ) );
	url.searchParams.set( 'level', edu === 'epal' ? 'epal' : 'gel' );
	return url.toString();
}

/**
 * Turn a service answer into [{ id, title, subtitle }]. Anything that is not a school with a title is ignored.
 *
 * @param {*} json Decoded answer.
 * @return {Array<{id:number,title:string,subtitle:string}>} Schools.
 */
export function parseSuggest( json ) {
	const items = Array.isArray( json?.items ) ? json.items : [];
	const out = [];
	for ( const item of items ) {
		const match = /^school:(\d{1,6})$/.exec( String( item?.id ?? '' ) );
		const title = typeof item?.title === 'string' ? item.title.trim() : '';
		if ( ! match || ! title ) {
			continue;
		}
		const id = Number( match[ 1 ] );
		if ( id < 1 ) {
			continue;
		}
		out.push( {
			id,
			title: title.slice( 0, 200 ),
			subtitle:
				typeof item.subtitle === 'string'
					? item.subtitle.slice( 0, 200 )
					: '',
		} );
	}
	return out;
}

/**
 * Options of the search request: no cookies, and no Referer header. The
 * browser still sends the Origin header on this cross-origin request, and the
 * service sees the IP address, so this only removes the page address.
 *
 * @param {AbortSignal} signal Signal to cancel the request.
 * @return {Object} Options for fetch().
 */
export const suggestRequestInit = ( signal ) => ( {
	signal,
	credentials: 'omit',
	referrerPolicy: 'no-referrer',
	headers: { Accept: 'application/json' },
} );
