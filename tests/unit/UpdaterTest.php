<?php
/**
 * Tests of the updater: feed, verification and download.
 *
 * The signing keys are generated in memory for each test run. They are test
 * keys, unrelated to any real key, and nothing is written to disk.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Tests;

use PHPUnit\Framework\TestCase;
use Panelladikes\Widgets\Updater\Config;
use Panelladikes\Widgets\Updater\Feed;
use Panelladikes\Widgets\Updater\Http;
use Panelladikes\Widgets\Updater\Updater;
use Panelladikes\Widgets\Updater\Verifier;

/**
 * Updater behaviour with a fake network.
 */
final class UpdaterTest extends TestCase {

	const PACKAGE_URL = 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/panelladikes-widgets.zip';
	const CDN_URL     = 'https://release-assets.githubusercontent.com/github-production-release-asset/1/abc';
	const BASENAME    = 'panelladikes-widgets/panelladikes-widgets.php';

	/**
	 * Test key pair (secret key + public key, raw bytes).
	 *
	 * @var string
	 */
	private $pair;

	/**
	 * Public key, base64.
	 *
	 * @var string
	 */
	private $public;

	/**
	 * Fake zip content.
	 *
	 * @var string
	 */
	private $zip = 'PK-test-package-content';

	/**
	 * Requests made through the fake network.
	 *
	 * @var string[]
	 */
	private $requests = array();

	/**
	 * Routes of the fake network: URL => response array or callable.
	 *
	 * @var array
	 */
	private $routes = array();

	/**
	 * Arguments of the last request.
	 *
	 * @var array
	 */
	private $last_args = array();

	/**
	 * Fresh state, new test keys.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->pair                              = sodium_crypto_sign_keypair();
		$this->public                            = base64_encode( sodium_crypto_sign_publickey( $this->pair ) );
		$this->requests                          = array();
		$this->routes                            = array();
		$GLOBALS['panelladikes_test_transients'] = array();
	}

	/**
	 * Signed release description for the fake zip.
	 *
	 * @param array $override Fields to replace.
	 * @return array
	 */
	private function release( array $override = array() ): array {
		$sha = hash( 'sha256', $this->zip );
		return array_merge(
			array(
				'slug'         => 'panelladikes-widgets',
				'version'      => '1.0.1',
				'package'      => self::PACKAGE_URL,
				'sha256'       => $sha,
				'signature'    => $this->sign( '1.0.1', $sha, $this->pair ),
				'tested'       => '7.1',
				'requires'     => '6.5',
				'requires_php' => '8.2',
			),
			$override
		);
	}

	/**
	 * Detached signature of the release message, base64.
	 *
	 * @param string $version Version.
	 * @param string $sha     SHA-256.
	 * @param string $pair    Key pair to sign with.
	 * @return string
	 */
	private function sign( string $version, string $sha, string $pair ): string {
		return base64_encode( sodium_crypto_sign_detached( Verifier::message( $version, $sha ), sodium_crypto_sign_secretkey( $pair ) ) );
	}

	/**
	 * The fake network.
	 *
	 * @param string $url  URL.
	 * @param array  $args Arguments.
	 * @return array|\WP_Error
	 */
	public function transport( string $url, array $args ) {
		$this->requests[] = $url;
		$this->last_args  = $args;
		$route            = $this->routes[ $url ] ?? new \WP_Error( 'http_request_failed', 'offline' );
		if ( is_callable( $route ) ) {
			$route = $route( $args );
		}
		return $route;
	}

	/**
	 * Route a feed and a package.
	 *
	 * @param array $release Release description.
	 * @param bool  $cdn     Whether the package goes through a redirect to the CDN.
	 * @return void
	 */
	private function serve( array $release, bool $cdn = true ): void {
		$this->routes[ Config::FEED_URL ] = array(
			'response' => array( 'code' => 200 ),
			'body'     => (string) json_encode( $release ),
		);
		$zip                              = $this->zip;
		$put                              = static function ( array $args ) use ( $zip ) {
			file_put_contents( $args['filename'], $zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return array( 'response' => array( 'code' => 200 ) );
		};
		if ( $cdn ) {
			$this->routes[ self::PACKAGE_URL ] = array(
				'response' => array( 'code' => 302 ),
				'headers'  => array( 'location' => self::CDN_URL ),
			);
			$this->routes[ self::CDN_URL ]     = $put;
		} else {
			$this->routes[ self::PACKAGE_URL ] = $put;
		}
	}

	/**
	 * Updater with the fake network and the test public key.
	 *
	 * @param string|null $key     Public key to use; the test key by default.
	 * @param string      $version Installed version.
	 * @return Updater
	 */
	private function updater( ?string $key = null, string $version = '1.0.0' ): Updater {
		$http = new Http( array( $this, 'transport' ) );
		return new Updater( new Feed( $http ), $http, $key ?? $this->public, self::BASENAME, $version );
	}


	public function test_verifier_accepts_a_correct_package(): void {
		$file = wp_tempnam();
		file_put_contents( $file, $this->zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->assertTrue( Verifier::verify( $file, $this->release(), $this->public ) );
		wp_delete_file( $file );
	}

	public function test_verifier_rejects_a_wrong_checksum(): void {
		$file = wp_tempnam();
		file_put_contents( $file, $this->zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = Verifier::verify( $file, $this->release( array( 'sha256' => str_repeat( 'a', 64 ) ) ), $this->public );
		$this->assertSame( 'panelladikes_bad_checksum', $result->get_error_code() );
		file_put_contents( $file, $this->zip . 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->assertSame( 'panelladikes_bad_checksum', Verifier::verify( $file, $this->release(), $this->public )->get_error_code() );
		wp_delete_file( $file );
	}

	public function test_verifier_rejects_a_signature_from_another_key(): void {
		$file = wp_tempnam();
		file_put_contents( $file, $this->zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$other  = sodium_crypto_sign_keypair();
		$sha    = hash( 'sha256', $this->zip );
		$result = Verifier::verify( $file, $this->release( array( 'signature' => $this->sign( '1.0.1', $sha, $other ) ) ), $this->public );
		$this->assertSame( 'panelladikes_bad_signature', $result->get_error_code() );
		wp_delete_file( $file );
	}

	public function test_verifier_rejects_a_signature_made_for_another_version(): void {
		$file = wp_tempnam();
		file_put_contents( $file, $this->zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$sha    = hash( 'sha256', $this->zip );
		$result = Verifier::verify( $file, $this->release( array( 'signature' => $this->sign( '0.9.0', $sha, $this->pair ) ) ), $this->public );
		$this->assertSame( 'panelladikes_bad_signature', $result->get_error_code() );
		wp_delete_file( $file );
	}

	public function test_verifier_refuses_without_a_valid_key(): void {
		$file = wp_tempnam();
		file_put_contents( $file, $this->zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		foreach ( array( '', 'not base64!', base64_encode( 'short' ) ) as $key ) {
			$this->assertSame( 'panelladikes_no_key', Verifier::verify( $file, $this->release(), $key )->get_error_code() );
		}
		wp_delete_file( $file );
	}

	public function test_production_key_constant_is_empty_or_a_valid_key(): void {
		$this->assertTrue( '' === Config::PUBLIC_KEY || Verifier::is_valid_key( Config::PUBLIC_KEY ) );
	}


	public function test_feed_validation_keeps_only_known_fields(): void {
		$clean = Feed::validate( $this->release( array( 'evil' => '<script>' ) ) );
		$this->assertArrayNotHasKey( 'evil', $clean );
		$this->assertSame( '1.0.1', $clean['version'] );
		$this->assertSame( '8.2', $clean['requires_php'] );
	}

	public function test_feed_validation_rejects_bad_descriptions(): void {
		$bad = array(
			array( 'slug' => 'other' ),
			array( 'version' => '1.0' ),
			array( 'version' => '1.0.1-beta' ),
			array( 'sha256' => 'ABC' ),
			array( 'signature' => 'short' ),
			array( 'package' => 'http://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/p.zip' ),
			array( 'package' => 'https://evil.example/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/p.zip' ),
			array( 'package' => 'https://github.com/someone/else/releases/download/v1/p.zip' ),
			array( 'package' => 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/p.tar' ),
			array( 'package' => self::PACKAGE_URL . '?x=1' ),
			array( 'package' => 'https://user@github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/p.zip' ),
			array( 'package' => 'https://objects.githubusercontent.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/p.zip' ),
		);
		foreach ( $bad as $override ) {
			$this->assertNull( Feed::validate( $this->release( $override ) ), (string) json_encode( $override ) );
		}
	}

	public function test_feed_fields_do_not_accept_a_trailing_newline(): void {
		$this->assertNull( Feed::validate( $this->release( array( 'version' => "1.0.1\n" ) ) ) );
		$this->assertNull( Feed::validate( $this->release( array( 'sha256' => hash( 'sha256', $this->zip ) . "\n" ) ) ) );
		$clean = Feed::validate( $this->release( array( 'tested' => "7.1\n", 'requires' => "6.5\n", 'requires_php' => "8.2\n" ) ) );
		$this->assertArrayNotHasKey( 'tested', $clean );
		$this->assertArrayNotHasKey( 'requires', $clean );
		$this->assertArrayNotHasKey( 'requires_php', $clean );
	}

	public function test_feed_drops_malformed_optional_fields(): void {
		$clean = Feed::validate( $this->release( array( 'tested' => '"><x', 'requires' => 7 ) ) );
		$this->assertArrayNotHasKey( 'tested', $clean );
		$this->assertArrayNotHasKey( 'requires', $clean );
	}


	public function test_http_allows_only_https_on_allowed_hosts(): void {
		$this->assertTrue( Http::is_allowed( Config::FEED_URL ) );
		$this->assertTrue( Http::is_allowed( self::CDN_URL ) );
		foreach ( array( 'http://github.com/x', 'https://evil.example/x', 'https://github.com.evil.example/x', 'https://github.com:8443/x', 'https://u:p@github.com/x', 'ftp://github.com/x', '//github.com/x', 'github.com/x' ) as $url ) {
			$this->assertFalse( Http::is_allowed( $url ), $url );
		}
	}

	public function test_http_blocks_a_redirect_to_a_foreign_host(): void {
		$this->routes['https://github.com/a'] = array(
			'response' => array( 'code' => 302 ),
			'headers'  => array( 'location' => 'https://evil.example/payload' ),
		);
		$result                               = ( new Http( array( $this, 'transport' ) ) )->get( 'https://github.com/a' );
		$this->assertSame( 'panelladikes_host_not_allowed', $result->get_error_code() );
		$this->assertSame( array( 'https://github.com/a' ), $this->requests );
	}

	public function test_http_blocks_a_downgrade_redirect_and_loops(): void {
		$this->routes['https://github.com/a'] = array(
			'response' => array( 'code' => 301 ),
			'headers'  => array( 'location' => 'http://github.com/b' ),
		);
		$this->assertSame( 'panelladikes_host_not_allowed', ( new Http( array( $this, 'transport' ) ) )->get( 'https://github.com/a' )->get_error_code() );

		$this->routes['https://github.com/loop'] = array(
			'response' => array( 'code' => 302 ),
			'headers'  => array( 'location' => 'https://github.com/loop' ),
		);
		$this->assertSame( 'panelladikes_too_many_redirects', ( new Http( array( $this, 'transport' ) ) )->get( 'https://github.com/loop' )->get_error_code() );
	}

	public function test_http_requests_carry_no_site_data(): void {
		$this->routes['https://github.com/a'] = array( 'response' => array( 'code' => 200 ) );
		( new Http( array( $this, 'transport' ) ) )->get( 'https://github.com/a', array( 'timeout' => 5 ) );
		$this->assertSame( array(), $this->last_args['cookies'] );
		$this->assertSame( 0, $this->last_args['redirection'] );
		$this->assertSame( 'PanelladikesWidgets', $this->last_args['user-agent'] );
		$this->assertArrayNotHasKey( 'body', $this->last_args );
	}


	public function test_feed_is_cached(): void {
		$this->serve( $this->release() );
		$feed = new Feed( new Http( array( $this, 'transport' ) ) );
		$this->assertSame( '1.0.1', $feed->get()['version'] );
		$count = count( $this->requests );
		$this->assertSame( '1.0.1', $feed->get()['version'] );
		$this->assertCount( $count, $this->requests, 'The second call uses the cache.' );
	}

	public function test_feed_failure_is_silent_and_remembered(): void {
		$feed = new Feed( new Http( array( $this, 'transport' ) ) );
		$this->assertNull( $feed->get() );
		$count = count( $this->requests );
		$this->assertNull( $feed->get() );
		$this->assertCount( $count, $this->requests, 'A failure is not retried on every call.' );
	}

	public function test_feed_ignores_non_200_and_broken_json(): void {
		$this->routes[ Config::FEED_URL ] = array( 'response' => array( 'code' => 404 ), 'body' => '{}' );
		$this->assertNull( ( new Feed( new Http( array( $this, 'transport' ) ) ) )->get() );
		$GLOBALS['panelladikes_test_transients'] = array();
		$this->routes[ Config::FEED_URL ]        = array( 'response' => array( 'code' => 200 ), 'body' => '{not json' );
		$this->assertNull( ( new Feed( new Http( array( $this, 'transport' ) ) ) )->get() );
	}

	public function test_feed_follows_the_release_redirect(): void {
		$this->routes[ Config::FEED_URL ] = array(
			'response' => array( 'code' => 302 ),
			'headers'  => array( 'location' => 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/update.json' ),
		);
		$this->routes['https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/update.json'] = array(
			'response' => array( 'code' => 200 ),
			'body'     => (string) json_encode( $this->release() ),
		);
		$this->assertSame( '1.0.1', ( new Feed( new Http( array( $this, 'transport' ) ) ) )->get()['version'] );
	}


	public function test_offer_describes_the_release_without_secrets_of_the_check(): void {
		$this->serve( $this->release() );
		$offer = $this->updater()->offer( false, array(), self::BASENAME );
		$this->assertSame( '1.0.1', $offer['version'] );
		$this->assertSame( self::PACKAGE_URL, $offer['package'] );
		$this->assertSame( 'panelladikes-widgets', $offer['slug'] );
		$this->assertArrayNotHasKey( 'sha256', $offer );
		$this->assertArrayNotHasKey( 'signature', $offer );
	}

	public function test_offer_leaves_other_plugins_alone(): void {
		$this->serve( $this->release() );
		$this->assertFalse( $this->updater()->offer( false, array(), 'other/other.php' ) );
		$this->assertSame( array( 'x' => 1 ), $this->updater()->offer( array( 'x' => 1 ), array(), 'other/other.php' ) );
		$this->assertSame( array(), $this->requests );
	}

	public function test_offer_needs_a_valid_key_and_a_reachable_feed(): void {
		$this->serve( $this->release() );
		$this->assertFalse( $this->updater( '' )->offer( false, array(), self::BASENAME ) );
		$this->assertSame( array(), $this->requests, 'Without a key nothing is requested.' );
		$this->routes = array();
		$this->assertFalse( $this->updater()->offer( false, array(), self::BASENAME ) );
	}


	public function test_download_returns_a_verified_file(): void {
		$this->serve( $this->release() );
		$path = $this->updater()->download( false, self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) );
		$this->assertIsString( $path );
		$this->assertSame( $this->zip, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertContains( self::CDN_URL, $this->requests, 'The package came through the CDN redirect.' );
		wp_delete_file( $path );
	}

	public function test_download_ignores_other_plugins(): void {
		$this->serve( $this->release() );
		$this->assertFalse( $this->updater()->download( false, 'https://downloads.wordpress.org/plugin/x.zip', null, array( 'plugin' => 'x/x.php' ) ) );
		$this->assertSame( 'earlier', $this->updater()->download( 'earlier', self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) ) );
		$this->assertSame( array(), $this->requests );
	}

	public function test_download_rejects_a_wrong_checksum_and_removes_the_file(): void {
		$this->serve( $this->release( array( 'sha256' => str_repeat( 'b', 64 ) ) ) );
		$result = $this->updater()->download( false, self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) );
		$this->assertSame( 'panelladikes_bad_checksum', $result->get_error_code() );
	}

	public function test_download_rejects_a_wrong_signature(): void {
		$sha = hash( 'sha256', $this->zip );
		$this->serve( $this->release( array( 'signature' => $this->sign( '1.0.1', $sha, sodium_crypto_sign_keypair() ) ) ) );
		$result = $this->updater()->download( false, self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) );
		$this->assertSame( 'panelladikes_bad_signature', $result->get_error_code() );
	}

	public function test_download_refuses_a_package_the_feed_did_not_announce(): void {
		$this->serve( $this->release() );
		$other                  = 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v9.9.9/panelladikes-widgets.zip';
		$this->routes[ $other ] = $this->routes[ self::PACKAGE_URL ];
		$result                 = $this->updater()->download( false, $other, null, array( 'plugin' => self::BASENAME ) );
		$this->assertSame( 'panelladikes_stale_update', $result->get_error_code() );
		$this->assertNotContains( $other, $this->requests );
	}

	public function test_download_refuses_a_foreign_host_even_if_the_feed_lists_it(): void {
		$this->serve( $this->release( array( 'package' => 'https://evil.example/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/p.zip' ) ) );
		$result = $this->updater()->download( false, 'https://github.com' . Config::PACKAGE_PATH_PREFIX . 'v1.0.1/p.zip', null, array( 'plugin' => self::BASENAME ) );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertNotContains( 'https://evil.example/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/p.zip', $this->requests );
	}

	public function test_download_refuses_a_redirect_to_a_foreign_host(): void {
		$this->serve( $this->release() );
		$this->routes[ self::PACKAGE_URL ] = array(
			'response' => array( 'code' => 302 ),
			'headers'  => array( 'location' => 'https://evil.example/payload.zip' ),
		);
		$result                            = $this->updater()->download( false, self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) );
		$this->assertSame( 'panelladikes_download_failed', $result->get_error_code() );
		$this->assertNotContains( 'https://evil.example/payload.zip', $this->requests );
	}

	public function test_download_handles_a_network_failure(): void {
		$this->serve( $this->release() );
		unset( $this->routes[ self::PACKAGE_URL ] );
		$result = $this->updater()->download( false, self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) );
		$this->assertSame( 'panelladikes_download_failed', $result->get_error_code() );
	}

	public function test_download_refuses_without_a_valid_key(): void {
		$this->serve( $this->release() );
		$result = $this->updater( '' )->download( false, self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) );
		$this->assertSame( 'panelladikes_no_key', $result->get_error_code() );
	}

	public function test_no_rollback_is_offered_or_installed(): void {
		foreach ( array( '1.0.0', '0.9.0' ) as $old ) {
			$this->setUp();
			$sha = hash( 'sha256', $this->zip );
			$pkg = 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v' . $old . '/panelladikes-widgets.zip';
			$this->serve( $this->release( array( 'version' => $old, 'package' => $pkg, 'signature' => $this->sign( $old, $sha, $this->pair ) ) ) );
			$this->routes[ $pkg ] = $this->routes[ self::PACKAGE_URL ];
			$this->assertFalse( $this->updater()->offer( false, array(), self::BASENAME ), $old );
			$result = $this->updater()->download( false, $pkg, null, array( 'plugin' => self::BASENAME ) );
			$this->assertSame( 'panelladikes_not_newer', $result->get_error_code(), $old );
			$this->assertNotContains( $pkg, $this->requests, 'Nothing is downloaded for an older version.' );
		}
		// A newer one is still offered.
		$this->setUp();
		$this->serve( $this->release() );
		$this->assertIsArray( $this->updater()->offer( false, array(), self::BASENAME ) );
	}

	public function test_package_address_is_strict(): void {
		$base = 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/';
		$bad  = array(
			$base . '../../../evil.zip',
			$base . '%2e%2e/evil.zip',
			$base . '..%2fevil.zip',
			$base . 'a%2fb.zip',
			$base . 'a\\b.zip',
			$base . 'x.zip?y=1',
			$base . 'x.zip#f',
			$base . 'x.zip ',
			$base . "x.zip\n",
			$base . 'sub/x.zip',
			str_replace( 'v1.0.1', 'v1.0', $base ) . 'x.zip',
			str_replace( 'v1.0.1', 'latest', $base ) . 'x.zip',
			str_replace( 'v1.0.1', 'v1.0.2', $base ) . 'x.zip',
			'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.1/x.exe',
			'https://github.com/PanelladikesApp/other/releases/download/v1.0.1/x.zip',
		);
		foreach ( $bad as $pkg ) {
			$this->assertNull( Feed::validate( $this->release( array( 'package' => $pkg ) ) ), $pkg );
		}
		$this->assertNotNull( Feed::validate( $this->release( array( 'package' => $base . 'panelladikes-widgets.zip' ) ) ) );
		$this->assertNotNull( Feed::validate( $this->release( array( 'package' => $base . 'panelladikes_widgets-1.0.1.zip' ) ) ) );
	}

	public function test_an_old_saved_feed_is_refreshed_once_before_giving_up(): void {
		// The saved description is for 1.0.1, while WordPress offers 1.0.2 (published later).
		$this->serve( $this->release() );
		$updater = $this->updater();
		$this->assertIsArray( $updater->offer( false, array(), self::BASENAME ) );

		$new_pkg = 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/download/v1.0.2/panelladikes-widgets.zip';
		$sha     = hash( 'sha256', $this->zip );
		$this->serve( $this->release( array( 'version' => '1.0.2', 'package' => $new_pkg, 'signature' => $this->sign( '1.0.2', $sha, $this->pair ) ) ) );
		$this->routes[ $new_pkg ] = $this->routes[ self::PACKAGE_URL ];
		$path                     = $updater->download( false, $new_pkg, null, array( 'plugin' => self::BASENAME ) );
		$this->assertIsString( $path );
		wp_delete_file( $path );
	}

	public function test_package_download_is_size_limited(): void {
		$this->serve( $this->release() );
		$seen                          = null;
		$this->routes[ self::CDN_URL ] = function ( array $args ) use ( &$seen ) {
			$seen = $args['limit_response_size'] ?? null;
			file_put_contents( $args['filename'], $this->zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return array( 'response' => array( 'code' => 200 ) );
		};
		$path                          = $this->updater()->download( false, self::PACKAGE_URL, null, array( 'plugin' => self::BASENAME ) );
		$this->assertSame( 10485760, $seen );
		wp_delete_file( $path );
	}

	public function test_hooks_are_registered_for_the_update_host(): void {
		$this->assertArrayHasKey( 'update_plugins_github.com', $GLOBALS['panelladikes_test_filters'] );
		$this->assertArrayHasKey( 'upgrader_pre_download', $GLOBALS['panelladikes_test_filters'] );
	}
}
