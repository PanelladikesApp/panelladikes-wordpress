<?php
/**
 * The single renderer used by the block and the shortcode.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Prints a widget as the `<div data-*>` that the embed script upgrades,
 * or as a plain `<iframe>`, from the same normalized attribute set.
 */
final class Renderer {

	const MODE_DIV    = 'div';
	const MODE_IFRAME = 'iframe';

	const SCRIPT_HANDLE = 'panelladikes-widgets-embed';

	/**
	 * Box shadow per contract value.
	 *
	 * @var string[]
	 */
	const SHADOWS = array(
		'sm' => '0 1px 3px rgba(0,0,0,.18)',
		'md' => '0 4px 12px rgba(0,0,0,.18)',
		'lg' => '0 10px 30px rgba(0,0,0,.22)',
	);

	/**
	 * Contract.
	 *
	 * @var Contract
	 */
	private $contract;

	/**
	 * Normalizer.
	 *
	 * @var Normalizer
	 */
	private $normalizer;

	/**
	 * Create a renderer.
	 *
	 * @param Contract|null $contract Contract; the bundled one by default.
	 */
	public function __construct( ?Contract $contract = null ) {
		$this->contract   = $contract ?? Contract::bundled();
		$this->normalizer = new Normalizer( $this->contract );
	}

	/**
	 * Render a widget.
	 *
	 * Returns an empty string when the attributes do not describe a widget.
	 * The script is enqueued only when markup that needs it is returned.
	 *
	 * @param array  $atts Raw attributes, never trusted.
	 * @param string $mode MODE_DIV or MODE_IFRAME; anything else is MODE_DIV.
	 * @return string
	 */
	public function render( array $atts, string $mode = self::MODE_DIV ): string {
		$set = $this->normalizer->normalize( $atts );
		if ( null === $set ) {
			return '';
		}
		if ( self::MODE_IFRAME === $mode ) {
			return $this->iframe( $set );
		}
		$this->enqueue_script();
		return $this->div( $set );
	}

	/**
	 * Enqueue the embed script once per page, from the fixed address.
	 *
	 * @return void
	 */
	private function enqueue_script(): void {
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$this->contract->script_url(),
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- The script is versioned by its own address.
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * The `<div data-*>` markup.
	 *
	 * @param array $set Normalized attributes.
	 * @return string
	 */
	private function div( array $set ): string {
		$attrs = array(
			'data-panelladikes-widget' => $set['type'],
			'data-edu'                 => $set['edu'],
		);
		if ( null !== $set['id'] ) {
			$attrs['data-id'] = (string) $set['id'];
		}
		$attrs['data-theme'] = $set['theme'];
		if ( null !== $set['pedio'] ) {
			$attrs['data-pedio'] = (string) $set['pedio'];
		}
		if ( null !== $set['width'] ) {
			$attrs['data-width'] = $set['width'];
			if ( 'left' !== $set['align'] ) {
				$attrs['data-align'] = $set['align'];
			}
		}
		if ( null !== $set['height'] ) {
			$attrs['data-height'] = (string) $set['height'];
		}
		if ( $set['border'] ) {
			$attrs['data-border'] = '1';
		}
		if ( 'none' !== $set['shadow'] ) {
			$attrs['data-shadow'] = $set['shadow'];
		}
		if ( $set['radius'] > 0 ) {
			$attrs['data-radius'] = (string) $set['radius'];
		}
		if ( ! $set['lazy'] ) {
			$attrs['data-lazy'] = 'false';
		}

		$html = '';
		foreach ( $attrs as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		return '<div' . $html . '></div>';
	}

	/**
	 * The `<iframe>` markup, for places where scripts do not run.
	 *
	 * Without the script the frame cannot follow the height of its content,
	 * so automatic height becomes the minimum height of the type.
	 *
	 * @param array $set Normalized attributes.
	 * @return string
	 */
	private function iframe( array $set ): string {
		$path = $set['type'] . ( null !== $set['id'] ? '/' . $set['id'] : '' );
		$src  = $this->contract->iframe_base() . $path
			. '?edu=' . $set['edu']
			. '&theme=' . $set['theme']
			. ( null !== $set['pedio'] ? '&pedio=' . $set['pedio'] : '' )
			. '&h=' . ( null !== $set['height'] ? 'fixed' : 'auto' );

		$min    = $this->contract->min_height( $set['type'] );
		$height = null !== $set['height'] ? $set['height'] : $min;

		if ( null === $set['width'] ) {
			$width = '100%';
		} else {
			$width = '%' === substr( $set['width'], -1 ) ? $set['width'] : $set['width'] . 'px';
		}

		$style  = 'display:block;width:' . $width . ';max-width:100%;height:' . $height . 'px;';
		$style .= 'min-height:' . ( null !== $set['height'] ? 0 : $min ) . 'px;';
		$style .= 'border:' . ( $set['border'] ? '1px solid rgba(128,128,128,.35)' : '0' ) . ';';
		$style .= 'border-radius:' . $set['radius'] . 'px;';
		if ( isset( self::SHADOWS[ $set['shadow'] ] ) ) {
			$style .= 'box-shadow:' . self::SHADOWS[ $set['shadow'] ] . ';';
		}
		if ( null !== $set['width'] && 'center' === $set['align'] ) {
			$style .= 'margin-left:auto;margin-right:auto;';
		} elseif ( null !== $set['width'] && 'right' === $set['align'] ) {
			$style .= 'margin-left:auto;';
		}

		$allow = 'mixanografiko' === $set['type'] ? ' allow="clipboard-write; web-share"' : '';

		return '<iframe src="' . esc_url( $src ) . '"'
			. ' title="' . esc_attr( $this->contract->title( $set['type'], $set['edu'] ) ) . '"'
			. ' loading="' . ( $set['lazy'] ? 'lazy' : 'eager' ) . '"'
			. ' referrerpolicy="origin"'
			. $allow
			. ' style="' . esc_attr( $style ) . '"></iframe>';
	}
}
