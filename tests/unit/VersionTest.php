<?php
/**
 * Keeps the release version identical everywhere it is written.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Version: one number in every file.
 */
final class VersionTest extends TestCase {

	/**
	 * Read a file of the plugin.
	 *
	 * @param string $path Path relative to the plugin root.
	 * @return string
	 */
	private function read( string $path ): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $path );
	}

	/**
	 * First capture of a pattern in a file.
	 *
	 * @param string $path    File.
	 * @param string $pattern Pattern with one group.
	 * @return string
	 */
	private function capture( string $path, string $pattern ): string {
		$this->assertSame( 1, preg_match( $pattern, $this->read( $path ), $m ), $path );
		return $m[1];
	}

	public function test_every_file_has_the_same_version(): void {
		$main     = $this->capture( 'panelladikes-widgets.php', '/^ \* Version:\s+(\S+)$/m' );
		$versions = array(
			'const VERSION'        => $this->capture( 'panelladikes-widgets.php', "/const VERSION\s+=\s+'([^']+)'/" ),
			'src/block.json'       => (string) json_decode( $this->read( 'src/block.json' ), true )['version'],
			'package.json'         => (string) json_decode( $this->read( 'package.json' ), true )['version'],
			'readme.txt'           => $this->capture( 'readme.txt', '/^Stable tag:\s+(\S+)$/m' ),
			'CHANGELOG.md'         => $this->capture( 'CHANGELOG.md', '/^## \[(\d+\.\d+\.\d+)\]/m' ),
			'translation template' => $this->capture( 'languages/panelladikes-widgets-el.po', '/Project-Id-Version: Panelladikes Widgets (\S+)\\\\n/' ),
		);
		foreach ( $versions as $where => $version ) {
			$this->assertSame( $main, $version, $where );
		}
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $main );
	}
}
