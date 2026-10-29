<?php
/**
 * Tests of the block render callback.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Tests;

use PHPUnit\Framework\TestCase;
use Panelladikes\Widgets\Block;
use Panelladikes\Widgets\Contract;
use Panelladikes\Widgets\Shortcode;

/**
 * Block: parity with the shortcode and with the contract.
 */
final class BlockTest extends TestCase {

	const WRAP = '<div class="wp-block-panelladikes-widget">';

	/**
	 * Fresh state for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['panelladikes_test_enqueued'] = array();
		$GLOBALS['panelladikes_test_actions']  = array();
	}

	/**
	 * Attributes of a freshly inserted block.
	 *
	 * @return array
	 */
	private function defaults(): array {
		$json = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/src/block.json' ), true );
		$out  = array();
		foreach ( $json['attributes'] as $name => $spec ) {
			$out[ $name ] = $spec['default'];
		}
		return $out;
	}

	public function test_registers_its_hooks(): void {
		Block::register();
		$this->assertArrayHasKey( 'init', $GLOBALS['panelladikes_test_actions'] );
		$this->assertArrayNotHasKey( 'enqueue_block_assets', $GLOBALS['panelladikes_test_actions'], 'The editor must not load the embed script on its own.' );
	}

	public function test_fresh_block_matches_the_shortcode(): void {
		$block = Block::render( $this->defaults() );
		$this->assertSame( self::WRAP . Shortcode::render( array( 'type' => 'calculator' ) ) . '</div>', $block );
		$this->assertStringContainsString( 'data-panelladikes-widget="calculator" data-edu="gel" data-theme="auto" data-height="720" data-radius="12"', $block );
	}

	public function test_school_block_matches_the_shortcode(): void {
		$attrs = array_merge( $this->defaults(), array( 'type' => 'sxoli', 'schoolId' => 321, 'schoolName' => 'x', 'edu' => 'epal' ) );
		$this->assertSame(
			self::WRAP . Shortcode::render( array( 'type' => 'sxoli', 'id' => '321', 'edu' => 'epal' ) ) . '</div>',
			Block::render( $attrs )
		);
	}

	public function test_stale_pedio_of_an_epal_block_is_not_printed(): void {
		$out = Block::render( array_merge( $this->defaults(), array( 'type' => 'program', 'edu' => 'epal', 'pedio' => 3 ) ) );
		$this->assertStringNotContainsString( 'data-pedio', $out );
		$this->assertSame( self::WRAP . Shortcode::render( array( 'type' => 'program', 'edu' => 'epal' ) ) . '</div>', $out );
	}

	public function test_school_block_without_id_renders_nothing(): void {
		$this->assertSame( '', Block::render( array_merge( $this->defaults(), array( 'type' => 'sxoli' ) ) ) );
		$this->assertSame( array(), $GLOBALS['panelladikes_test_enqueued'] );
	}

	public function test_saved_markup_is_not_trusted(): void {
		$evil = '"><script>alert(1)</script>';
		$out  = Block::render( array( 'type' => 'baseis', 'theme' => $evil, 'width' => $evil, 'schoolName' => $evil ) );
		$this->assertStringNotContainsString( '<script', $out );
		$this->assertSame( '', Block::render( array( 'type' => $evil ) ) );
		$this->assertSame( '', Block::render( 'not an array' ) );
	}

	public function test_block_json_agrees_with_the_contract(): void {
		$json     = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/src/block.json' ), true );
		$contract = Contract::bundled();
		$attrs    = $json['attributes'];
		$this->assertSame( $contract->types(), $attrs['type']['enum'] );
		foreach ( array( 'edu', 'theme', 'align', 'shadow' ) as $name ) {
			$this->assertSame( $contract->enum( $name ), $attrs[ $name ]['enum'], $name );
			$this->assertSame( $contract->spec( $name )['default'], $attrs[ $name ]['default'], $name );
		}
		$this->assertSame( array( 'gel' ), $contract->spec( 'pedio' )['edu'] );
		$this->assertSame( $contract->spec( 'radius' )['default'], $attrs['radius']['default'] );
		$this->assertSame( $contract->spec( 'border' )['default'], $attrs['border']['default'] );
		$this->assertSame( $contract->spec( 'lazy' )['default'], $attrs['lazy']['default'] );
	}
}
