/**
 * Attribute rules shared with the PHP renderer.
 *
 * The limits, defaults and allowed values come from the bundled contract.
 * `normalize()` and `dataAttributes()` mirror the server so that the editor
 * preview shows what the front end will print.
 */
import contract from '../../contract/embed-contract.v2.json';

const SPEC = contract.attributes;

export const TYPES = contract.types;
export const EDU = SPEC.edu.enum;
export const THEMES = SPEC.theme.enum;
export const ALIGNS = SPEC.align.enum;
export const SHADOWS = SPEC.shadow.enum;
export const LIMITS = {
	schoolId: SPEC.id,
	pedio: SPEC.pedio,
	height: SPEC.height,
	radius: SPEC.radius,
	widthPx: SPEC.width.px,
	widthPercent: SPEC.width.percent,
};
export const DEFAULT_RADIUS = SPEC.radius.default;

const oneOf = ( list, value, fallback ) =>
	typeof value === 'string' && list.includes( value ) ? value : fallback;

const intIn = ( value, { min, max }, fallback ) => {
	const number =
		typeof value === 'string' && /^\d{1,6}$/.test( value )
			? Number( value )
			: value;
	return Number.isInteger( number ) && number >= min && number <= max
		? number
		: fallback;
};

const toBool = ( value, fallback ) => {
	if ( typeof value === 'boolean' ) {
		return value;
	}
	if ( value === 1 || value === 0 ) {
		return value === 1;
	}
	if ( typeof value === 'string' ) {
		const word = value.trim().toLowerCase();
		if ( [ '1', 'true', 'yes', 'on' ].includes( word ) ) {
			return true;
		}
		if ( [ '0', 'false', 'no', 'off' ].includes( word ) ) {
			return false;
		}
	}
	return fallback;
};

/**
 * Width: null for full width, "NNN" for pixels, "NN%" for a percentage.
 *
 * @param {unknown} value Raw value.
 * @return {string|null} Normalized width.
 */
export function normalizeWidth( value ) {
	const text = String( typeof value === 'number' ? value : ( value ?? '' ) )
		.trim()
		.toLowerCase();
	let match = /^(\d{1,4})(px)?$/.exec( text );
	if ( match ) {
		const px = Number( match[ 1 ] );
		const { min, max } = LIMITS.widthPx;
		return px >= min && px <= max ? String( px ) : null;
	}
	match = /^(\d{1,3})%$/.exec( text );
	if ( match ) {
		const percent = Number( match[ 1 ] );
		const { min, max } = LIMITS.widthPercent;
		return percent !== 100 && percent >= min && percent <= max
			? `${ percent }%`
			: null;
	}
	return null;
}

/**
 * Height in pixels, or null for automatic height.
 *
 * @param {string}  type  Widget type.
 * @param {unknown} value Raw value: empty for the default of the type, "auto", or pixels.
 * @return {number|null} Normalized height.
 */
export function normalizeHeight( type, value ) {
	if ( typeof value === 'string' && value.trim().toLowerCase() === 'auto' ) {
		return null;
	}
	const fallback =
		typeof contract.defaultHeight[ type ] === 'number'
			? contract.defaultHeight[ type ]
			: null;
	return intIn( value, LIMITS.height, fallback );
}

/**
 * Normalize block attributes. Returns null when no widget can be shown.
 *
 * @param {Object} raw Block attributes.
 * @return {Object|null} Normalized set, or null.
 */
export function normalize( raw = {} ) {
	if ( ! TYPES.includes( raw.type ) ) {
		return null;
	}
	let id = null;
	if ( SPEC.id.types.includes( raw.type ) ) {
		id =
			intIn( raw.schoolId, LIMITS.schoolId, null ) ??
			intIn( raw.id, LIMITS.schoolId, null );
		if ( id === null ) {
			return null;
		}
	}
	const edu = oneOf( EDU, raw.edu, SPEC.edu.default );
	return {
		type: raw.type,
		edu,
		theme: oneOf( THEMES, raw.theme, SPEC.theme.default ),
		id,
		pedio: SPEC.pedio.edu.includes( edu )
			? intIn( raw.pedio, LIMITS.pedio, null )
			: null,
		width: normalizeWidth( raw.width ),
		align: oneOf( ALIGNS, raw.align, SPEC.align.default ),
		height: normalizeHeight( raw.type, raw.height ),
		radius: intIn( raw.radius, LIMITS.radius, DEFAULT_RADIUS ),
		shadow: oneOf( SHADOWS, raw.shadow, SPEC.shadow.default ),
		border: toBool( raw.border, SPEC.border.default ),
		lazy: toBool( raw.lazy, SPEC.lazy.default ),
	};
}

/**
 * The `data-*` attributes of the widget element, in the order the server prints them.
 *
 * @param {Object} set Normalized set from normalize().
 * @return {Array<Array<string>>} List of [name, value].
 */
export function dataAttributes( set ) {
	const list = [
		[ 'data-panelladikes-widget', set.type ],
		[ 'data-edu', set.edu ],
	];
	if ( set.id !== null ) {
		list.push( [ 'data-id', String( set.id ) ] );
	}
	list.push( [ 'data-theme', set.theme ] );
	if ( set.pedio !== null ) {
		list.push( [ 'data-pedio', String( set.pedio ) ] );
	}
	if ( set.width !== null ) {
		list.push( [ 'data-width', set.width ] );
		if ( set.align !== 'left' ) {
			list.push( [ 'data-align', set.align ] );
		}
	}
	if ( set.height !== null ) {
		list.push( [ 'data-height', String( set.height ) ] );
	}
	if ( set.border ) {
		list.push( [ 'data-border', '1' ] );
	}
	if ( set.shadow !== 'none' ) {
		list.push( [ 'data-shadow', set.shadow ] );
	}
	if ( set.radius > 0 ) {
		list.push( [ 'data-radius', String( set.radius ) ] );
	}
	if ( ! set.lazy ) {
		list.push( [ 'data-lazy', 'false' ] );
	}
	return list;
}

/**
 * How the width attribute is edited: "full", "px" or "percent".
 *
 * @param {string} width Block width attribute.
 * @return {string} Mode.
 */
export function widthMode( width ) {
	const value = normalizeWidth( width );
	if ( value === null ) {
		return 'full';
	}
	return value.endsWith( '%' ) ? 'percent' : 'px';
}

/**
 * How the height attribute is edited: "default", "auto" or "fixed".
 *
 * @param {string} height Block height attribute.
 * @return {string} Mode.
 */
export function heightMode( height ) {
	if (
		typeof height === 'string' &&
		height.trim().toLowerCase() === 'auto'
	) {
		return 'auto';
	}
	return intIn( height, LIMITS.height, null ) === null ? 'default' : 'fixed';
}
