<?php
/**
 * Tests of the privacy policy text.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Tests;

use PHPUnit\Framework\TestCase;
use Panelladikes\Widgets\Privacy;

/**
 * Privacy: the suggested policy text is offered under the plugin name.
 */
final class PrivacyTest extends TestCase {

	/**
	 * Fresh state for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['panelladikes_test_privacy'] = array();
		$GLOBALS['panelladikes_test_actions'] = array();
	}

	public function test_registers_on_admin_init(): void {
		Privacy::register();
		$this->assertArrayHasKey( 'admin_init', $GLOBALS['panelladikes_test_actions'] );
		$this->assertSame( array( Privacy::class, 'add_content' ), $GLOBALS['panelladikes_test_actions']['admin_init'][0] );
	}

	public function test_offers_the_text_under_the_plugin_name(): void {
		Privacy::add_content();
		$this->assertCount( 1, $GLOBALS['panelladikes_test_privacy'] );
		list( $name, $content ) = $GLOBALS['panelladikes_test_privacy'][0];
		$this->assertSame( 'Panelladikes Widgets', $name );
		$this->assertSame( $content, Privacy::content() );
	}

	public function test_text_covers_the_data_that_leaves_the_site(): void {
		$text = Privacy::content();
		$this->assertStringContainsString( 'panelladikes.edu.gr', $text );
		$this->assertStringContainsString( 'Mixanografiko', $text );
		$this->assertStringContainsString( 'email address', $text );
		$this->assertStringContainsString( 'sets no cookies', $text );
		$this->assertSame( 3, substr_count( $text, '<p>' ) );
	}
}
