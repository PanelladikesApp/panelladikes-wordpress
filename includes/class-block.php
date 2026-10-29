<?php
/**
 * The dynamic block.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the block and prints it with the shared renderer.
 */
final class Block {

	const NAME = 'panelladikes/widget';

	/**
	 * Hook the block into WordPress.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Register the block from the built files.
	 *
	 * @return void
	 */
	public static function register_block(): void {
		$dir = dirname( __DIR__ ) . '/build';
		if ( ! is_readable( $dir . '/block.json' ) ) {
			return;
		}
		$type = register_block_type(
			$dir,
			array( 'render_callback' => array( __CLASS__, 'render' ) )
		);
		if ( $type && ! empty( $type->editor_script_handles ) ) {
			foreach ( $type->editor_script_handles as $handle ) {
				wp_set_script_translations( $handle, 'panelladikes-widgets', dirname( __DIR__ ) . '/languages' );
			}
		}
	}

	/**
	 * Render callback: same markup as the shortcode, inside the block wrapper.
	 *
	 * @param array $attributes Saved block attributes, never trusted.
	 * @return string
	 */
	public static function render( $attributes = array() ): string {
		$widget = ( new Renderer() )->render( is_array( $attributes ) ? $attributes : array() );
		if ( '' === $widget ) {
			return '';
		}
		return '<div ' . get_block_wrapper_attributes() . '>' . $widget . '</div>';
	}
}
