<?php
/**
 * WordPress integration of the updater.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Updater;

defined( 'ABSPATH' ) || exit;

/**
 * Offers new releases through the `Update URI` mechanism and checks every package before it is installed.
 */
final class Updater {

	/**
	 * Release feed.
	 *
	 * @var Feed
	 */
	private $feed;

	/**
	 * HTTP client.
	 *
	 * @var Http
	 */
	private $http;

	/**
	 * Public key, base64.
	 *
	 * @var string
	 */
	private $public_key;

	/**
	 * Plugin file relative to the plugins folder.
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Version that is installed now.
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Create the updater.
	 *
	 * @param Feed   $feed       Release feed.
	 * @param Http   $http       HTTP client.
	 * @param string $public_key Base64 Ed25519 public key.
	 * @param string $basename   Plugin file relative to the plugins folder.
	 * @param string $version    Installed version.
	 */
	public function __construct( Feed $feed, Http $http, string $public_key, string $basename, string $version ) {
		$this->feed            = $feed;
		$this->http            = $http;
		$this->public_key      = $public_key;
		$this->basename        = $basename;
		$this->current_version = $version;
	}

	/**
	 * Hook the updater into WordPress.
	 *
	 * @return void
	 */
	public static function register(): void {
		$http    = new Http();
		$updater = new self(
			new Feed( $http ),
			$http,
			Config::PUBLIC_KEY,
			plugin_basename( dirname( __DIR__, 2 ) . '/panelladikes-widgets.php' ),
			defined( '\\Panelladikes\\Widgets\\VERSION' ) ? \Panelladikes\Widgets\VERSION : '0.0.0'
		);
		$host    = wp_parse_url( Config::FEED_URL, PHP_URL_HOST );
		add_filter( 'update_plugins_' . $host, array( $updater, 'offer' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $updater, 'download' ), 10, 4 );
	}

	/**
	 * Filter `update_plugins_{hostname}`: describe the newest release for this plugin only.
	 *
	 * @param array|false $update      Update data from another source.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin file.
	 * @return array|false
	 */
	public function offer( $update, $plugin_data = array(), $plugin_file = '' ) {
		if ( $plugin_file !== $this->basename || ! Verifier::is_valid_key( $this->public_key ) ) {
			return $update;
		}
		$release = $this->feed->get();
		if ( null === $release || ! $this->is_newer( $release ) ) {
			return $update;
		}
		return array_diff_key( $release, array_flip( array( 'sha256', 'signature' ) ) );
	}

	/**
	 * Filter `upgrader_pre_download`: download the package ourselves and verify it.
	 *
	 * Returns false for packages of other plugins so WordPress handles them as usual.
	 *
	 * @param false|string|\WP_Error $reply      Earlier result.
	 * @param string                 $package    Package address.
	 * @param mixed                  $upgrader   Upgrader instance.
	 * @param array                  $hook_extra Context of the upgrade.
	 * @return false|string|\WP_Error Path of the verified zip, or an error.
	 */
	public function download( $reply, $package = '', $upgrader = null, $hook_extra = array() ) {
		if ( false !== $reply || ! $this->is_ours( (string) $package, $hook_extra ) ) {
			return $reply;
		}

		if ( ! Verifier::is_valid_key( $this->public_key ) ) {
			return new \WP_Error( 'panelladikes_no_key', __( 'The update could not be verified.', 'panelladikes-widgets' ) );
		}

		$release = $this->feed->get();
		if ( null === $release || $release['package'] !== $package ) {
			// The saved description may be older than the update WordPress offers: read it once more.
			delete_site_transient( Config::TRANSIENT );
			$release = $this->feed->get();
		}
		if ( null === $release || $release['package'] !== $package ) {
			return new \WP_Error( 'panelladikes_stale_update', __( 'The update information has changed. Check for updates again and retry.', 'panelladikes-widgets' ) );
		}
		if ( ! $this->is_newer( $release ) ) {
			return new \WP_Error( 'panelladikes_not_newer', __( 'The update is not newer than the installed version and was not installed.', 'panelladikes-widgets' ) );
		}

		$file = wp_tempnam( $package );
		if ( ! $file ) {
			return new \WP_Error( 'panelladikes_no_tempfile', __( 'The update could not be downloaded.', 'panelladikes-widgets' ) );
		}

		$response = $this->http->get(
			$package,
			array(
				'timeout'             => Config::PACKAGE_TIMEOUT,
				'stream'              => true,
				'filename'            => $file,
				'limit_response_size' => Config::PACKAGE_MAX_BYTES,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_delete_file( $file );
			return new \WP_Error( 'panelladikes_download_failed', __( 'The update could not be downloaded.', 'panelladikes-widgets' ) );
		}

		$result = Verifier::verify( $file, $release, $this->public_key );
		if ( is_wp_error( $result ) ) {
			wp_delete_file( $file );
			return $result;
		}
		return $file;
	}

	/**
	 * Whether the release is newer than the installed version (never offer or install a rollback).
	 *
	 * @param array $release Validated description.
	 * @return bool
	 */
	private function is_newer( array $release ): bool {
		return version_compare( $release['version'], $this->current_version, '>' );
	}

	/**
	 * Whether an upgrade concerns this plugin.
	 *
	 * @param string $package    Package address.
	 * @param mixed  $hook_extra Context of the upgrade.
	 * @return bool
	 */
	private function is_ours( string $package, $hook_extra ): bool {
		if ( is_array( $hook_extra ) && ( $hook_extra['plugin'] ?? null ) === $this->basename ) {
			return true;
		}
		return 0 === strpos( $package, 'https://github.com' . Config::PACKAGE_PATH_PREFIX );
	}
}
