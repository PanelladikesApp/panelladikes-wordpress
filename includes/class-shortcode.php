<?php
/**
 * The [panelladikes] shortcode.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode front end of the renderer.
 *
 * Example: [panelladikes type="sxoli" edu="gel" id="123" theme="light"]
 */
final class Shortcode {

	const TAG = 'panelladikes';

	/**
	 * Attributes the shortcode understands. Anything else is dropped.
	 *
	 * @var string[]
	 */
	const ATTRIBUTES = array( 'type', 'edu', 'id', 'pedio', 'theme', 'width', 'align', 'height', 'radius', 'shadow', 'border', 'lazy' );

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		$atts = shortcode_atts( array_fill_keys( self::ATTRIBUTES, '' ), is_array( $atts ) ? $atts : array(), self::TAG );
		return ( new Renderer() )->render( $atts );
	}
}
