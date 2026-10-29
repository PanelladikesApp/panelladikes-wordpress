<?php
/**
 * Validation and normalization of widget attributes.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Turns untrusted attributes (shortcode, saved block markup) into a safe, complete set.
 *
 * Every value is checked against the contract. Anything outside the whitelist
 * or the limits falls back to the default; nothing is passed through unchecked.
 */
final class Normalizer {

	/**
	 * Contract.
	 *
	 * @var Contract
	 */
	private $contract;

	/**
	 * Create a normalizer.
	 *
	 * @param Contract $contract Contract to validate against.
	 */
	public function __construct( Contract $contract ) {
		$this->contract = $contract;
	}

	/**
	 * Normalize raw attributes.
	 *
	 * Returns null when no widget can be shown: unknown or missing type, or a
	 * school widget without a valid school id.
	 *
	 * Result keys: type, edu, theme, id (int|null), pedio (int|null, only for gel),
	 * width (null for full width, otherwise "NNN" in pixels or "NN%"),
	 * align, height (int pixels|null for automatic), radius, shadow,
	 * border (bool), lazy (bool).
	 *
	 * @param array $raw Raw attributes.
	 * @return array|null
	 */
	public function normalize( array $raw ): ?array {
		$type = $raw['type'] ?? null;
		if ( ! is_string( $type ) || ! in_array( $type, $this->contract->types(), true ) ) {
			return null;
		}

		$id_spec = $this->contract->spec( 'id' );
		$id      = null;
		if ( in_array( $type, $id_spec['types'] ?? array(), true ) ) {
			// The block attribute is `schoolId`; the shortcode and the data attribute use `id`.
			$id = $this->int_in( $raw['schoolId'] ?? null, $id_spec, null );
			if ( null === $id ) {
				$id = $this->int_in( $raw['id'] ?? null, $id_spec, null );
			}
			if ( null === $id ) {
				return null;
			}
		}

		$width = $this->width( $raw['width'] ?? null );
		$edu   = $this->enum( 'edu', $raw['edu'] ?? null );

		// The field (pedio) only exists for the General Lyceum; EPAL works with sectors.
		$pedio_spec = $this->contract->spec( 'pedio' );
		$pedio      = in_array( $edu, $pedio_spec['edu'] ?? array(), true )
			? $this->int_in( $raw['pedio'] ?? null, $pedio_spec, null )
			: null;

		return array(
			'type'   => $type,
			'edu'    => $edu,
			'theme'  => $this->enum( 'theme', $raw['theme'] ?? null ),
			'id'     => $id,
			'pedio'  => $pedio,
			'width'  => $width,
			'align'  => $this->enum( 'align', $raw['align'] ?? null ),
			'height' => $this->height( $type, $raw['height'] ?? null ),
			'radius' => $this->int_in( $raw['radius'] ?? null, $this->contract->spec( 'radius' ), $this->default_int( 'radius' ) ),
			'shadow' => $this->enum( 'shadow', $raw['shadow'] ?? null ),
			'border' => $this->bool( $raw['border'] ?? null, $this->default_bool( 'border' ) ),
			'lazy'   => $this->bool( $raw['lazy'] ?? null, $this->default_bool( 'lazy' ) ),
		);
	}

	/**
	 * Value of an enumerated attribute, or its default.
	 *
	 * @param string $name  Attribute name.
	 * @param mixed  $value Raw value.
	 * @return string
	 */
	private function enum( string $name, $value ): string {
		$allowed = $this->contract->enum( $name );
		if ( is_string( $value ) && in_array( $value, $allowed, true ) ) {
			return $value;
		}
		$default = $this->contract->spec( $name )['default'] ?? '';
		return is_string( $default ) ? $default : '';
	}

	/**
	 * Integer inside the limits of a specification.
	 *
	 * Accepts integers and strings of one to six digits.
	 *
	 * @param mixed    $value   Raw value.
	 * @param array    $spec    Specification with `min` and `max`.
	 * @param int|null $fallback Value used when invalid.
	 * @return int|null
	 */
	private function int_in( $value, array $spec, ?int $fallback ): ?int {
		$min = isset( $spec['min'] ) ? (int) $spec['min'] : 0;
		$max = isset( $spec['max'] ) ? (int) $spec['max'] : 0;
		if ( is_string( $value ) && 1 === preg_match( '/^\d{1,6}$/', $value ) ) {
			$value = (int) $value;
		}
		if ( is_int( $value ) && $value >= $min && $value <= $max ) {
			return $value;
		}
		return $fallback;
	}

	/**
	 * Boolean from a boolean, 0/1 or a common word.
	 *
	 * @param mixed $value   Raw value.
	 * @param bool  $fallback Value used when invalid.
	 * @return bool
	 */
	private function bool( $value, bool $fallback ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( 1 === $value || 0 === $value ) {
			return 1 === $value;
		}
		if ( is_string( $value ) ) {
			$word = strtolower( trim( $value ) );
			if ( in_array( $word, array( '1', 'true', 'yes', 'on' ), true ) ) {
				return true;
			}
			if ( in_array( $word, array( '0', 'false', 'no', 'off' ), true ) ) {
				return false;
			}
		}
		return $fallback;
	}

	/**
	 * Width: null for full width, "NNN" for pixels, "NN%" for a percentage.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null
	 */
	private function width( $value ): ?string {
		$spec = $this->contract->spec( 'width' );
		if ( is_int( $value ) ) {
			$value = (string) $value;
		}
		if ( ! is_string( $value ) ) {
			return null;
		}
		$value = strtolower( trim( $value ) );

		if ( 1 === preg_match( '/^(\d{1,4})(px)?$/', $value, $m ) ) {
			$px = (int) $m[1];
			if ( $px >= (int) ( $spec['px']['min'] ?? 0 ) && $px <= (int) ( $spec['px']['max'] ?? 0 ) ) {
				return (string) $px;
			}
			return null;
		}
		if ( 1 === preg_match( '/^(\d{1,3})%$/', $value, $m ) ) {
			$pct = (int) $m[1];
			if ( 100 === $pct ) {
				return null;
			}
			if ( $pct >= (int) ( $spec['percent']['min'] ?? 0 ) && $pct <= (int) ( $spec['percent']['max'] ?? 0 ) ) {
				return $pct . '%';
			}
		}
		return null;
	}

	/**
	 * Height in pixels, or null for automatic height.
	 *
	 * An empty value means the default of the type; the word "auto" forces automatic height.
	 *
	 * @param string $type  Widget type.
	 * @param mixed  $value Raw value.
	 * @return int|null
	 */
	private function height( string $type, $value ): ?int {
		if ( is_string( $value ) && 'auto' === strtolower( trim( $value ) ) ) {
			return null;
		}
		return $this->int_in( $value, $this->contract->spec( 'height' ), $this->contract->default_height( $type ) );
	}

	/**
	 * Integer default of an attribute.
	 *
	 * @param string $name Attribute name.
	 * @return int
	 */
	private function default_int( string $name ): int {
		return (int) ( $this->contract->spec( $name )['default'] ?? 0 );
	}

	/**
	 * Boolean default of an attribute.
	 *
	 * @param string $name Attribute name.
	 * @return bool
	 */
	private function default_bool( string $name ): bool {
		return true === ( $this->contract->spec( $name )['default'] ?? false );
	}
}
