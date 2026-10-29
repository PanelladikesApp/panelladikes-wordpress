<?php
/**
 * Read-only access to the embed contract (contract/embed-contract.v2.json).
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * The embed contract: allowed types, attributes, limits and defaults.
 */
final class Contract {

	/**
	 * Decoded contract.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Shared instance.
	 *
	 * @var Contract|null
	 */
	private static $instance = null;

	/**
	 * Wrap a decoded contract.
	 *
	 * @param array $data Decoded contract.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * The contract bundled with the plugin.
	 *
	 * @return Contract
	 */
	public static function bundled(): Contract {
		if ( null === self::$instance ) {
			$path = dirname( __DIR__ ) . '/contract/embed-contract.v2.json';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a local file shipped with the plugin.
			$json = file_get_contents( $path );
			$data = is_string( $json ) ? json_decode( $json, true ) : null;
			if ( ! is_array( $data ) ) {
				$data = array();
			}
			self::$instance = new self( $data );
		}
		return self::$instance;
	}

	/**
	 * Origin that serves the script and the iframes. Never configurable.
	 *
	 * @return string
	 */
	public function origin(): string {
		return 'https://panelladikes.edu.gr';
	}

	/**
	 * Absolute URL of the embed script. Never configurable.
	 *
	 * @return string
	 */
	public function script_url(): string {
		return $this->origin() . '/js/embed.v2.js';
	}

	/**
	 * Absolute base URL of the iframe documents.
	 *
	 * @return string
	 */
	public function iframe_base(): string {
		return $this->origin() . '/embed/v2/';
	}

	/**
	 * Widget types.
	 *
	 * @return string[]
	 */
	public function types(): array {
		return $this->list_of( $this->data['types'] ?? array() );
	}

	/**
	 * Allowed values of an enumerated attribute.
	 *
	 * @param string $name Attribute name.
	 * @return string[]
	 */
	public function enum( string $name ): array {
		return $this->list_of( $this->data['attributes'][ $name ]['enum'] ?? array() );
	}

	/**
	 * Specification of one attribute.
	 *
	 * @param string $name Attribute name.
	 * @return array
	 */
	public function spec( string $name ): array {
		$spec = $this->data['attributes'][ $name ] ?? array();
		return is_array( $spec ) ? $spec : array();
	}

	/**
	 * Default height of a type: an integer in pixels, or null for automatic height.
	 *
	 * @param string $type Widget type.
	 * @return int|null
	 */
	public function default_height( string $type ): ?int {
		$value = $this->data['defaultHeight'][ $type ] ?? 'auto';
		return is_int( $value ) ? $value : null;
	}

	/**
	 * Minimum height of a type in pixels.
	 *
	 * @param string $type Widget type.
	 * @return int
	 */
	public function min_height( string $type ): int {
		$value = $this->data['minHeight'][ $type ] ?? 480;
		return is_int( $value ) ? $value : 480;
	}

	/**
	 * Accessible title of the iframe of a type and education system.
	 *
	 * @param string $type Widget type.
	 * @param string $edu  Education system.
	 * @return string
	 */
	public function title( string $type, string $edu ): string {
		$value = $this->data['titles'][ $type ][ $edu ] ?? '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Keep only strings of a list.
	 *
	 * @param mixed $items Candidate list.
	 * @return string[]
	 */
	private function list_of( $items ): array {
		return is_array( $items ) ? array_values( array_filter( $items, 'is_string' ) ) : array();
	}
}
