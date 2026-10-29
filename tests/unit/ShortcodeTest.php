<?php
/**
 * Tests of the shortcode front end.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Tests;

use PHPUnit\Framework\TestCase;
use Panelladikes\Widgets\Shortcode;

/**
 * Shortcode: registration and attribute handling.
 */
final class ShortcodeTest extends TestCase {

	/**
	 * Fresh state for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['panelladikes_test_enqueued']   = array();
		$GLOBALS['panelladikes_test_shortcodes'] = array();
	}

	public function test_registers_the_panelladikes_tag(): void {
		Shortcode::register();
		$this->assertArrayHasKey( 'panelladikes', $GLOBALS['panelladikes_test_shortcodes'] );
		$this->assertIsCallable( $GLOBALS['panelladikes_test_shortcodes']['panelladikes'] );
	}

	public function test_school_with_id(): void {
		$this->assertSame(
			'<div data-panelladikes-widget="sxoli" data-edu="gel" data-id="123" data-theme="light" data-radius="12"></div>',
			Shortcode::render( array( 'type' => 'sxoli', 'id' => '123', 'theme' => 'light' ) )
		);
	}

	public function test_pedio_only_for_gel(): void {
		$this->assertStringContainsString( 'data-pedio="2"', Shortcode::render( array( 'type' => 'program', 'pedio' => '2' ) ) );
		$this->assertStringNotContainsString( 'data-pedio', Shortcode::render( array( 'type' => 'program', 'edu' => 'epal', 'pedio' => '2' ) ) );
	}

	public function test_school_without_id_renders_nothing(): void {
		$this->assertSame( '', Shortcode::render( array( 'type' => 'sxoli' ) ) );
	}

	public function test_no_attributes_renders_nothing(): void {
		$this->assertSame( '', Shortcode::render( '' ) );
		$this->assertSame( '', Shortcode::render( array() ) );
	}

	public function test_unknown_attributes_are_dropped(): void {
		$html = Shortcode::render( array( 'type' => 'baseis', 'onclick' => 'x()', 'style' => 'color:red', 'class' => 'a' ) );
		$this->assertSame( '<div data-panelladikes-widget="baseis" data-edu="gel" data-theme="auto" data-radius="12"></div>', $html );
	}

	public function test_hostile_attributes_are_neutralised(): void {
		$html = Shortcode::render( array( 'type' => 'baseis', 'theme' => '"><script>alert(1)</script>' ) );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( 'data-theme="auto"', $html );
	}
}
