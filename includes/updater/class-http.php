<?php
/**
 * HTTPS GET restricted to the allowed hosts.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Updater;

defined( 'ABSPATH' ) || exit;

/**
 * Follows redirects by hand so every hop can be checked.
 *
 * Requests carry no cookies, no body and nothing that identifies the site.
 */
final class Http {

	/**
	 * Function that performs one request without following redirects.
	 *
	 * @var callable
	 */
	private $transport;

	/**
	 * Create the client.
	 *
	 * @param callable|null $transport Replacement for wp_remote_get(), used by tests.
	 */
	public function __construct( ?callable $transport = null ) {
		$this->transport = $transport ?? 'wp_remote_get';
	}

	/**
	 * Whether a URL may be requested: HTTPS, an allowed host, no credentials, default port.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_allowed( string $url ): bool {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) ) {
			return false;
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ) {
			return false;
		}
		return in_array( strtolower( $parts['host'] ?? '' ), Config::ALLOWED_HOSTS, true );
	}

	/**
	 * GET a URL and return the final response.
	 *
	 * @param string $url  URL to fetch.
	 * @param array  $args Extra request arguments (timeout, stream, filename, limit_response_size).
	 * @return array|\WP_Error Response, or an error when a hop is not allowed or the request fails.
	 */
	public function get( string $url, array $args = array() ) {
		$args = array_merge(
			$args,
			array(
				'redirection'        => 0,
				'cookies'            => array(),
				'reject_unsafe_urls' => true,
				'user-agent'         => 'PanelladikesWidgets',
				'headers'            => array( 'Accept' => '*/*' ),
				'sslverify'          => true,
			)
		);

		for ( $hop = 0; $hop <= Config::MAX_REDIRECTS; $hop++ ) {
			if ( ! self::is_allowed( $url ) ) {
				return new \WP_Error( 'panelladikes_host_not_allowed', 'Address not allowed.' );
			}
			$response = call_user_func( $this->transport, $url, $args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code >= 300 && $code < 400 ) {
				$location = wp_remote_retrieve_header( $response, 'location' );
				if ( ! is_string( $location ) || '' === $location ) {
					return new \WP_Error( 'panelladikes_bad_redirect', 'Redirect without a target.' );
				}
				$url = $location;
				continue;
			}
			return $response;
		}
		return new \WP_Error( 'panelladikes_too_many_redirects', 'Too many redirects.' );
	}
}
