<?php
/**
 * The update description published with each release.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Updater;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches, validates and caches update.json.
 */
final class Feed {

	/**
	 * HTTP client.
	 *
	 * @var Http
	 */
	private $http;

	/**
	 * Create the feed reader.
	 *
	 * @param Http $http HTTP client.
	 */
	public function __construct( Http $http ) {
		$this->http = $http;
	}

	/**
	 * The current release description, or null when none is available.
	 *
	 * Failures are silent and remembered for a while so the site does not retry on every request.
	 *
	 * @return array|null
	 */
	public function get(): ?array {
		$cached = get_site_transient( Config::TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached['ok'] ? $cached['data'] : null;
		}

		$data = $this->fetch();
		set_site_transient(
			Config::TRANSIENT,
			array(
				'ok'   => null !== $data,
				'data' => $data,
			),
			null !== $data ? Config::FEED_TTL : Config::FEED_FAIL_TTL
		);
		return $data;
	}

	/**
	 * Download and validate update.json.
	 *
	 * @return array|null
	 */
	private function fetch(): ?array {
		$response = $this->http->get(
			Config::FEED_URL,
			array(
				'timeout'             => Config::FEED_TIMEOUT,
				'limit_response_size' => Config::FEED_MAX_BYTES,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $json ) ? self::validate( $json ) : null;
	}

	/**
	 * Check every field and keep only what is needed.
	 *
	 * @param array $json Decoded update.json.
	 * @return array|null Clean description, or null when anything required is wrong.
	 */
	public static function validate( array $json ): ?array {
		$version   = $json['version'] ?? null;
		$sha256    = $json['sha256'] ?? null;
		$signature = $json['signature'] ?? null;
		$package   = $json['package'] ?? null;

		if ( ( $json['slug'] ?? null ) !== Config::SLUG ) {
			return null;
		}
		if ( ! is_string( $version ) || 1 !== preg_match( '/^\d{1,4}\.\d{1,4}\.\d{1,4}$/D', $version ) ) {
			return null;
		}
		if ( ! is_string( $sha256 ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $sha256 ) ) {
			return null;
		}
		if ( ! is_string( $signature ) || SODIUM_CRYPTO_SIGN_BYTES !== strlen( (string) base64_decode( $signature, true ) ) ) {
			return null;
		}
		if ( ! is_string( $package ) || 1 !== preg_match( Config::PACKAGE_PATTERN, $package ) ) {
			return null;
		}
		// The release folder of the package must be the version it announces.
		if ( false === strpos( $package, '/releases/download/v' . $version . '/' ) ) {
			return null;
		}

		$out = array(
			'slug'      => Config::SLUG,
			'version'   => $version,
			'package'   => $package,
			'sha256'    => $sha256,
			'signature' => $signature,
			'url'       => 'https://github.com/PanelladikesApp/panelladikes-wordpress',
		);
		foreach ( array( 'tested', 'requires', 'requires_php' ) as $key ) {
			$value = $json[ $key ] ?? null;
			if ( is_string( $value ) && 1 === preg_match( '/^\d{1,2}(\.\d{1,2}){1,2}$/D', $value ) ) {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}
}
