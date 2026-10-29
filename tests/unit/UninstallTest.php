<?php
/**
 * Tests of uninstall.php.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Tests;

use PHPUnit\Framework\TestCase;
use Panelladikes\Widgets\Updater\Config;

/**
 * Uninstall: removes the cached release description and nothing else.
 */
final class UninstallTest extends TestCase {

	public function test_it_does_nothing_unless_wordpress_is_uninstalling_the_plugin(): void {
		$file   = dirname( __DIR__, 2 ) . '/uninstall.php';
		$output = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( 'include ' . var_export( $file, true ) . '; echo "REACHED";' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
		$this->assertSame( '', $output, 'Without WP_UNINSTALL_PLUGIN the file must stop before doing anything.' );
	}

	public function test_it_deletes_the_update_feed_transient_only(): void {
		$GLOBALS['panelladikes_test_transients'] = array(
			Config::TRANSIENT => array( 'ok' => true ),
			'other_transient' => 'keep',
		);
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'panelladikes-widgets/panelladikes-widgets.php' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';
		$this->assertArrayNotHasKey( Config::TRANSIENT, $GLOBALS['panelladikes_test_transients'] );
		$this->assertSame( 'keep', $GLOBALS['panelladikes_test_transients']['other_transient'] );
	}

	public function test_the_file_is_part_of_the_package(): void {
		$ignore = (string) file_get_contents( dirname( __DIR__, 2 ) . '/.distignore' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertNotContains( 'uninstall.php', array_map( 'trim', explode( "\n", $ignore ) ) );
	}
}
