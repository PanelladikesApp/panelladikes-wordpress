import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { normalize, dataAttributes } from './lib/attributes';
import { loadEmbedScript } from './lib/embed-script';

const DEBOUNCE_MS = 400;

/**
 * Live preview: builds the widget element and asks the embed script to upgrade it.
 *
 * The editor may run inside an iframe, so the embed script is loaded into the
 * document that holds the block, and only when a widget block is shown.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.attributes Block attributes.
 */
export default function Preview( { attributes } ) {
	const hostRef = useRef();
	const [ unavailable, setUnavailable ] = useState( false );
	const set = normalize( attributes );
	const key = set ? JSON.stringify( dataAttributes( set ) ) : '';

	useEffect( () => {
		const host = hostRef.current;
		if ( ! host || ! key ) {
			return undefined;
		}
		const attrs = JSON.parse( key );
		let cancelled = false;

		const draw = () => {
			loadEmbedScript( host.ownerDocument )
				.then( ( embed ) => {
					if ( cancelled ) {
						return;
					}
					const element = host.ownerDocument.createElement( 'div' );
					attrs.forEach( ( [ name, value ] ) =>
						element.setAttribute( name, value )
					);
					host.replaceChildren( element );
					embed.render( element );
				} )
				.catch( () => {
					if ( ! cancelled ) {
						setUnavailable( true );
					}
				} );
		};

		setUnavailable( false );
		const timer = setTimeout( draw, DEBOUNCE_MS );
		return () => {
			cancelled = true;
			clearTimeout( timer );
			host.replaceChildren();
		};
	}, [ key ] );

	return (
		<>
			<div ref={ hostRef } />
			{ unavailable && (
				<p role="status">
					{ __(
						'The preview could not be loaded. The widget will still appear on the page.',
						'panelladikes-widgets'
					) }
				</p>
			) }
		</>
	);
}
