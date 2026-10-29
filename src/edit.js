import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import Preview from './preview';
import SchoolSearch from './school-search';
import {
	ALIGNS,
	LIMITS,
	THEMES,
	SHADOWS,
	heightMode,
	normalize,
	widthMode,
} from './lib/attributes';

const TYPE_OPTIONS = [
	{
		value: 'calculator',
		label: __( 'Points calculator', 'panelladikes-widgets' ),
	},
	{ value: 'baseis', label: __( 'Admission bases', 'panelladikes-widgets' ) },
	{ value: 'program', label: __( 'Exam schedule', 'panelladikes-widgets' ) },
	{ value: 'sxoli', label: __( 'School details', 'panelladikes-widgets' ) },
	{
		value: 'mixanografiko',
		label: __( 'Mixanografiko', 'panelladikes-widgets' ),
	},
];
const EDU_OPTIONS = [
	{
		value: 'gel',
		label: __( 'General Lyceum (GEL)', 'panelladikes-widgets' ),
	},
	{
		value: 'epal',
		label: __( 'Vocational Lyceum (EPAL)', 'panelladikes-widgets' ),
	},
];
const THEME_LABELS = {
	auto: __( 'Automatic', 'panelladikes-widgets' ),
	light: __( 'Light', 'panelladikes-widgets' ),
	dark: __( 'Dark', 'panelladikes-widgets' ),
};
const ALIGN_LABELS = {
	left: __( 'Left', 'panelladikes-widgets' ),
	center: __( 'Center', 'panelladikes-widgets' ),
	right: __( 'Right', 'panelladikes-widgets' ),
};
const SHADOW_LABELS = {
	none: __( 'None', 'panelladikes-widgets' ),
	sm: __( 'Small', 'panelladikes-widgets' ),
	md: __( 'Medium', 'panelladikes-widgets' ),
	lg: __( 'Large', 'panelladikes-widgets' ),
};
const toOptions = ( list, labels ) =>
	list.map( ( value ) => ( { value, label: labels[ value ] } ) );

const DEFAULT_PIXEL_WIDTH = '600';
const DEFAULT_PERCENT_WIDTH = '50%';
const DEFAULT_FIXED_HEIGHT = '720';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const {
		type,
		edu,
		schoolId,
		schoolName,
		pedio,
		theme,
		width,
		align,
		height,
		radius,
		shadow,
		border,
		lazy,
	} = attributes;
	const wMode = widthMode( width );
	const hMode = heightMode( height );
	const isSchool = type === 'sxoli';
	const ready = normalize( attributes ) !== null;

	const numberOrZero = ( value ) => {
		const number = parseInt( value, 10 );
		return Number.isFinite( number ) ? number : 0;
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Widget', 'panelladikes-widgets' ) }>
					<SelectControl
						label={ __( 'Widget type', 'panelladikes-widgets' ) }
						value={ type }
						options={ TYPE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { type: value } )
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'School type', 'panelladikes-widgets' ) }
						value={ edu }
						options={ EDU_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( {
								edu: value,
								schoolId: 0,
								schoolName: '',
								// The exam field only exists for the General Lyceum.
								...( value === 'epal' ? { pedio: 0 } : {} ),
							} )
						}
						__nextHasNoMarginBottom
					/>
					{ isSchool && (
						<>
							<SchoolSearch
								edu={ edu }
								onSelect={ ( item ) =>
									setAttributes( {
										schoolId: item.id,
										schoolName: item.title,
									} )
								}
							/>
							<TextControl
								label={ __(
									'School number',
									'panelladikes-widgets'
								) }
								help={
									schoolId > 0 && schoolName
										? schoolName
										: __(
												'Filled in when you pick a school above. You can also type it.',
												'panelladikes-widgets'
										  )
								}
								type="number"
								min={ LIMITS.schoolId.min }
								max={ LIMITS.schoolId.max }
								value={ schoolId > 0 ? String( schoolId ) : '' }
								onChange={ ( value ) =>
									setAttributes( {
										schoolId: numberOrZero( value ),
										schoolName: '',
									} )
								}
								__nextHasNoMarginBottom
							/>
						</>
					) }
					{ edu === 'gel' && (
						<SelectControl
							label={ __( 'Exam field', 'panelladikes-widgets' ) }
							value={ String( pedio ) }
							options={ [
								{
									value: '0',
									label: __(
										'Not set',
										'panelladikes-widgets'
									),
								},
								...[ 1, 2, 3, 4 ].map( ( n ) => ( {
									value: String( n ),
									label: sprintf(
										/* translators: %d: field number, 1 to 4 */
										__(
											'Field %d',
											'panelladikes-widgets'
										),
										n
									),
								} ) ),
							] }
							onChange={ ( value ) =>
								setAttributes( {
									pedio: numberOrZero( value ),
								} )
							}
							__nextHasNoMarginBottom
						/>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Appearance', 'panelladikes-widgets' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Theme', 'panelladikes-widgets' ) }
						value={ theme }
						options={ toOptions( THEMES, THEME_LABELS ) }
						onChange={ ( value ) =>
							setAttributes( { theme: value } )
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Width', 'panelladikes-widgets' ) }
						value={ wMode }
						options={ [
							{
								value: 'full',
								label: __(
									'Full width',
									'panelladikes-widgets'
								),
							},
							{
								value: 'px',
								label: __( 'Pixels', 'panelladikes-widgets' ),
							},
							{
								value: 'percent',
								label: __( 'Percent', 'panelladikes-widgets' ),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								width: {
									full: '',
									px: DEFAULT_PIXEL_WIDTH,
									percent: DEFAULT_PERCENT_WIDTH,
								}[ value ],
							} )
						}
						__nextHasNoMarginBottom
					/>
					{ wMode !== 'full' && (
						<>
							<TextControl
								label={
									wMode === 'px'
										? __(
												'Width in pixels',
												'panelladikes-widgets'
										  )
										: __(
												'Width in percent',
												'panelladikes-widgets'
										  )
								}
								type="number"
								min={
									wMode === 'px'
										? LIMITS.widthPx.min
										: LIMITS.widthPercent.min
								}
								max={
									wMode === 'px'
										? LIMITS.widthPx.max
										: LIMITS.widthPercent.max - 1
								}
								value={ parseInt( width, 10 ) || '' }
								onChange={ ( value ) =>
									setAttributes( {
										width:
											wMode === 'px'
												? String( value )
												: `${ value }%`,
									} )
								}
								__nextHasNoMarginBottom
							/>
							<SelectControl
								label={ __(
									'Alignment',
									'panelladikes-widgets'
								) }
								value={ align }
								options={ toOptions( ALIGNS, ALIGN_LABELS ) }
								onChange={ ( value ) =>
									setAttributes( { align: value } )
								}
								__nextHasNoMarginBottom
							/>
						</>
					) }
					<SelectControl
						label={ __( 'Height', 'panelladikes-widgets' ) }
						value={ hMode }
						options={ [
							{
								value: 'default',
								label: __(
									'Default for this widget',
									'panelladikes-widgets'
								),
							},
							{
								value: 'auto',
								label: __(
									'Fit the content',
									'panelladikes-widgets'
								),
							},
							{
								value: 'fixed',
								label: __(
									'Fixed (pixels)',
									'panelladikes-widgets'
								),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								height: {
									default: '',
									auto: 'auto',
									fixed: DEFAULT_FIXED_HEIGHT,
								}[ value ],
							} )
						}
						__nextHasNoMarginBottom
					/>
					{ hMode === 'fixed' && (
						<TextControl
							label={ __(
								'Height in pixels',
								'panelladikes-widgets'
							) }
							type="number"
							min={ LIMITS.height.min }
							max={ LIMITS.height.max }
							value={ height }
							onChange={ ( value ) =>
								setAttributes( { height: String( value ) } )
							}
							__nextHasNoMarginBottom
						/>
					) }
					<RangeControl
						label={ __( 'Corner radius', 'panelladikes-widgets' ) }
						value={ radius }
						min={ LIMITS.radius.min }
						max={ LIMITS.radius.max }
						onChange={ ( value ) =>
							setAttributes( { radius: value ?? 0 } )
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Shadow', 'panelladikes-widgets' ) }
						value={ shadow }
						options={ toOptions( SHADOWS, SHADOW_LABELS ) }
						onChange={ ( value ) =>
							setAttributes( { shadow: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Border', 'panelladikes-widgets' ) }
						checked={ border }
						onChange={ ( value ) =>
							setAttributes( { border: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __(
							'Load when scrolled into view',
							'panelladikes-widgets'
						) }
						checked={ lazy }
						onChange={ ( value ) =>
							setAttributes( { lazy: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ ready ? (
					<Preview attributes={ attributes } />
				) : (
					<p>
						{ __(
							'Choose a school in the block settings to see the widget.',
							'panelladikes-widgets'
						) }
					</p>
				) }
			</div>
		</>
	);
}
