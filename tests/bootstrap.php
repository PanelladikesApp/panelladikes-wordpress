<?php
/**
 * Bootstrap for the unit tests.
 *
 * The unit tests run without WordPress. The few WordPress functions the code
 * calls are replaced by minimal stand-ins; integration tests against a real
 * WordPress live separately.
 *
 * @package Panelladikes_Widgets
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['panelladikes_test_enqueued']   = array();
$GLOBALS['panelladikes_test_shortcodes'] = array();

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Stand-in for esc_attr().
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Stand-in for esc_url().
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url( $url ) {
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	/**
	 * Stand-in for wp_enqueue_script(): records the call.
	 *
	 * @param string $handle    Handle.
	 * @param string $src       Source.
	 * @param array  $deps      Dependencies.
	 * @param mixed  $ver       Version.
	 * @param mixed  $in_footer Footer flag or arguments.
	 * @return void
	 */
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		$GLOBALS['panelladikes_test_enqueued'][ $handle ] = array(
			'src'       => $src,
			'deps'      => $deps,
			'ver'       => $ver,
			'in_footer' => $in_footer,
		);
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	/**
	 * Stand-in for add_shortcode(): records the callback.
	 *
	 * @param string   $tag      Tag.
	 * @param callable $callback Callback.
	 * @return void
	 */
	function add_shortcode( $tag, $callback ) {
		$GLOBALS['panelladikes_test_shortcodes'][ $tag ] = $callback;
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	/**
	 * Stand-in for shortcode_atts(): keeps only known keys.
	 *
	 * @param array $pairs     Defaults.
	 * @param array $atts      Given attributes.
	 * @return array
	 */
	function shortcode_atts( $pairs, $atts ) {
		$out = array();
		foreach ( $pairs as $name => $default ) {
			$out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
		}
		return $out;
	}
}

if ( ! function_exists( 'get_block_wrapper_attributes' ) ) {
	/**
	 * Stand-in for get_block_wrapper_attributes().
	 *
	 * @return string
	 */
	function get_block_wrapper_attributes() {
		return 'class="wp-block-panelladikes-widget"';
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Stand-in for add_action(): records the hook.
	 *
	 * @param string   $hook     Hook.
	 * @param callable $callback Callback.
	 * @return void
	 */
	function add_action( $hook, $callback ) {
		$GLOBALS['panelladikes_test_actions'][ $hook ][] = $callback;
	}
}


define( 'HOUR_IN_SECONDS', 3600 );
$GLOBALS['panelladikes_test_transients'] = array();

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error.
	 */
	class WP_Error {
		/**
		 * Code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Constructor.
		 *
		 * @param string $code    Code.
		 * @param string $message Message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * Code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Stand-in for is_wp_error().
	 *
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stand-in for __().
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Stand-in for wp_parse_url().
	 *
	 * @param string $url       URL.
	 * @param int    $component Component.
	 * @return mixed
	 */
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * Stand-in.
	 *
	 * @param array $response Response.
	 * @return int
	 */
	function wp_remote_retrieve_response_code( $response ) {
		return $response['response']['code'] ?? 0;
	}

	/**
	 * Stand-in.
	 *
	 * @param array $response Response.
	 * @return string
	 */
	function wp_remote_retrieve_body( $response ) {
		return $response['body'] ?? '';
	}

	/**
	 * Stand-in.
	 *
	 * @param array  $response Response.
	 * @param string $header   Header.
	 * @return string
	 */
	function wp_remote_retrieve_header( $response, $header ) {
		return $response['headers'][ $header ] ?? '';
	}
}

if ( ! function_exists( 'get_site_transient' ) ) {
	/**
	 * Stand-in.
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	function get_site_transient( $key ) {
		return $GLOBALS['panelladikes_test_transients'][ $key ] ?? false;
	}

	/**
	 * Stand-in.
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	function set_site_transient( $key, $value ) {
		$GLOBALS['panelladikes_test_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_site_transient' ) ) {
	/**
	 * Stand-in.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	function delete_site_transient( $key ) {
		unset( $GLOBALS['panelladikes_test_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_tempnam' ) ) {
	/**
	 * Stand-in.
	 *
	 * @return string
	 */
	function wp_tempnam() {
		return (string) tempnam( sys_get_temp_dir(), 'pwtest' );
	}

	/**
	 * Stand-in.
	 *
	 * @param string $file Path.
	 * @return void
	 */
	function wp_delete_file( $file ) {
		if ( is_file( $file ) ) {
			unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Stand-in: returns the text unchanged.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function wp_kses_post( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
	/**
	 * Stand-in: records the call.
	 *
	 * @param string $name    Name.
	 * @param string $content Content.
	 * @return void
	 */
	function wp_add_privacy_policy_content( $name, $content ) {
		$GLOBALS['panelladikes_test_privacy'][] = array( $name, $content );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stand-in: records the filter.
	 *
	 * @param string   $hook     Hook.
	 * @param callable $callback Callback.
	 * @return void
	 */
	function add_filter( $hook, $callback ) {
		$GLOBALS['panelladikes_test_filters'][ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Stand-in.
	 *
	 * @param string $file Path.
	 * @return string
	 */
	function plugin_basename( $file ) {
		return 'panelladikes-widgets/' . basename( $file );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-contract.php';
require_once dirname( __DIR__ ) . '/includes/class-normalizer.php';
require_once dirname( __DIR__ ) . '/includes/class-renderer.php';
require_once dirname( __DIR__ ) . '/includes/class-shortcode.php';
require_once dirname( __DIR__ ) . '/includes/class-privacy.php';
require_once dirname( __DIR__ ) . '/includes/class-block.php';
require_once dirname( __DIR__ ) . '/includes/updater/bootstrap.php';
