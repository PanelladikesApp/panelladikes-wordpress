import { useEffect, useState } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import {
	DEBOUNCE_MS,
	MIN_QUERY_LENGTH,
	normalizeQuery,
	parseSuggest,
	suggestRequestInit,
	suggestUrl,
} from './lib/suggest';

/**
 * Search box for choosing a school.
 *
 * @param {Object}                                        props          Component props.
 * @param {string}                                        props.edu      "gel" or "epal".
 * @param {(item: { id: number, title: string }) => void} props.onSelect Called with the chosen school.
 */
export default function SchoolSearch( { edu, onSelect } ) {
	const [ query, setQuery ] = useState( '' );
	const [ items, setItems ] = useState( [] );
	const [ status, setStatus ] = useState( 'idle' );

	useEffect( () => {
		const text = normalizeQuery( query );
		if ( text.length < MIN_QUERY_LENGTH ) {
			setItems( [] );
			setStatus( 'idle' );
			return undefined;
		}
		const controller = new AbortController();
		const timer = setTimeout( async () => {
			setStatus( 'loading' );
			try {
				const response = await fetch(
					suggestUrl( text, edu ),
					suggestRequestInit( controller.signal )
				);
				if ( ! response.ok ) {
					throw new Error( 'search failed' );
				}
				const found = parseSuggest( await response.json() );
				setItems( found );
				setStatus( found.length ? 'ok' : 'empty' );
			} catch {
				if ( controller.signal.aborted ) {
					return;
				}
				setItems( [] );
				setStatus( 'error' );
			}
		}, DEBOUNCE_MS );
		return () => {
			clearTimeout( timer );
			controller.abort();
		};
	}, [ query, edu ] );

	return (
		<div>
			<TextControl
				label={ __( 'Search for a school', 'panelladikes-widgets' ) }
				value={ query }
				onChange={ setQuery }
				__nextHasNoMarginBottom
			/>
			<div role="status" aria-live="polite">
				{ status === 'loading' && (
					<p>{ __( 'Searching…', 'panelladikes-widgets' ) }</p>
				) }
				{ status === 'empty' && (
					<p>{ __( 'No schools found.', 'panelladikes-widgets' ) }</p>
				) }
				{ status === 'error' && (
					<p>
						{ __(
							'The search is not available right now. Enter the school number below instead.',
							'panelladikes-widgets'
						) }
					</p>
				) }
			</div>
			{ items.length > 0 && (
				<ul className="panelladikes-school-results">
					{ items.map( ( item ) => (
						<li key={ item.id }>
							<button
								type="button"
								className="panelladikes-school-result"
								onClick={ () => {
									onSelect( item );
									setQuery( '' );
								} }
							>
								<span className="panelladikes-school-result__title">
									{ item.title }
								</span>
								{ item.subtitle && (
									<span className="panelladikes-school-result__subtitle">
										{ item.subtitle }
									</span>
								) }
							</button>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}
