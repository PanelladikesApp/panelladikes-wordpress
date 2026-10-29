<?php
/**
 * Integrity and authenticity checks for a downloaded package.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Updater;

defined( 'ABSPATH' ) || exit;

/**
 * Checks the SHA-256 of the zip and the Ed25519 signature that covers it.
 */
final class Verifier {

	/**
	 * The text that is signed: it ties the signature to the plugin, the version and the exact file.
	 *
	 * @param string $version Version.
	 * @param string $sha256  Lowercase hex SHA-256 of the zip.
	 * @return string
	 */
	public static function message( string $version, string $sha256 ): string {
		return Config::SLUG . ':' . $version . ':' . $sha256;
	}

	/**
	 * Whether a base64 string is a usable Ed25519 public key.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	public static function is_valid_key( string $key ): bool {
		return SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES === strlen( (string) base64_decode( $key, true ) );
	}

	/**
	 * Verify a downloaded file.
	 *
	 * @param string $file       Path of the zip.
	 * @param array  $release    Validated description from Feed::validate().
	 * @param string $public_key Base64 Ed25519 public key.
	 * @return true|\WP_Error
	 */
	public static function verify( string $file, array $release, string $public_key ) {
		if ( ! self::is_valid_key( $public_key ) ) {
			return new \WP_Error( 'panelladikes_no_key', __( 'The update could not be verified.', 'panelladikes-widgets' ) );
		}
		if ( ! is_readable( $file ) ) {
			return new \WP_Error( 'panelladikes_unreadable', __( 'The update could not be verified.', 'panelladikes-widgets' ) );
		}

		$actual = hash_file( 'sha256', $file );
		if ( ! is_string( $actual ) || ! hash_equals( $release['sha256'], $actual ) ) {
			return new \WP_Error( 'panelladikes_bad_checksum', __( 'The downloaded update does not match its checksum and was not installed.', 'panelladikes-widgets' ) );
		}

		$signature = base64_decode( $release['signature'], true );
		$key       = base64_decode( $public_key, true );
		$valid     = is_string( $signature ) && is_string( $key ) && sodium_crypto_sign_verify_detached(
			$signature,
			self::message( $release['version'], $actual ),
			$key
		);
		if ( ! $valid ) {
			return new \WP_Error( 'panelladikes_bad_signature', __( 'The signature of the downloaded update is not valid and it was not installed.', 'panelladikes-widgets' ) );
		}
		return true;
	}
}
