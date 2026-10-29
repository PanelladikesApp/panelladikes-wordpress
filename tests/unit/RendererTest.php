<?php
/**
 * Tests of the shared renderer.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Tests;

use PHPUnit\Framework\TestCase;
use Panelladikes\Widgets\Renderer;

/**
 * Renderer: output shape, whitelist, limits, escaping, script loading.
 */
final class RendererTest extends TestCase {

	/**
	 * Fresh state for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['panelladikes_test_enqueued'] = array();
	}

	/**
	 * Render with the bundled contract.
	 *
	 * @param array  $atts Attributes.
	 * @param string $mode Mode.
	 * @return string
	 */
	private function render( array $atts, string $mode = 'div' ): string {
		return ( new Renderer() )->render( $atts, $mode );
	}

	public function test_calculator_defaults(): void {
		$this->assertSame(
			'<div data-panelladikes-widget="calculator" data-edu="gel" data-theme="auto" data-height="720" data-radius="12"></div>',
			$this->render( array( 'type' => 'calculator' ) )
		);
	}

	public function test_auto_height_types_have_no_height_attribute(): void {
		foreach ( array( 'baseis', 'program' ) as $type ) {
			$this->assertStringNotContainsString( 'data-height', $this->render( array( 'type' => $type ) ), $type );
		}
	}

	public function test_height_auto_overrides_the_default_of_the_type(): void {
		$this->assertStringNotContainsString( 'data-height', $this->render( array( 'type' => 'calculator', 'height' => 'auto' ) ) );
	}

	public function test_full_attribute_set_in_builder_order(): void {
		$html = $this->render(
			array(
				'type'   => 'program',
				'edu'    => 'gel',
				'theme'  => 'dark',
				'pedio'  => '3',
				'width'  => '600',
				'align'  => 'center',
				'height' => '800',
				'border' => 'true',
				'shadow' => 'lg',
				'radius' => '8',
				'lazy'   => 'false',
			)
		);
		$this->assertSame(
			'<div data-panelladikes-widget="program" data-edu="gel" data-theme="dark" data-pedio="3" data-width="600" data-align="center" data-height="800" data-border="1" data-shadow="lg" data-radius="8" data-lazy="false"></div>',
			$html
		);
	}

	public function test_pedio_is_ignored_for_epal(): void {
		$this->assertStringNotContainsString( 'data-pedio', $this->render( array( 'type' => 'program', 'edu' => 'epal', 'pedio' => 2 ) ) );
		$this->assertStringNotContainsString( 'pedio', $this->render( array( 'type' => 'program', 'edu' => 'epal', 'pedio' => 2 ), 'iframe' ) );
		$this->assertStringContainsString( 'data-pedio="2"', $this->render( array( 'type' => 'program', 'edu' => 'gel', 'pedio' => 2 ) ) );
		$this->assertStringContainsString( 'pedio=2', $this->render( array( 'type' => 'program', 'edu' => 'gel', 'pedio' => 2 ), 'iframe' ) );
		// An invalid system falls back to gel, where the field applies.
		$this->assertStringContainsString( 'data-pedio="3"', $this->render( array( 'type' => 'baseis', 'edu' => 'nope', 'pedio' => 3 ) ) );
	}

	public function test_school_widget_requires_an_id(): void {
		foreach ( array( array(), array( 'id' => '' ), array( 'id' => '0' ), array( 'id' => 'abc' ), array( 'id' => '1000000' ), array( 'id' => '-5' ), array( 'id' => 12.5 ) ) as $extra ) {
			$this->assertSame( '', $this->render( array( 'type' => 'sxoli' ) + $extra ), (string) json_encode( $extra ) );
		}
		$this->assertArrayNotHasKey( 'panelladikes-widgets-embed', $GLOBALS['panelladikes_test_enqueued'] );
	}

	public function test_school_widget_with_id(): void {
		$this->assertStringContainsString( 'data-id="123"', $this->render( array( 'type' => 'sxoli', 'id' => '123' ) ) );
		$this->assertStringContainsString( 'data-id="999999"', $this->render( array( 'type' => 'sxoli', 'id' => 999999 ) ) );
	}

	public function test_block_attribute_school_id_maps_to_id(): void {
		$this->assertStringContainsString( 'data-id="77"', $this->render( array( 'type' => 'sxoli', 'schoolId' => 77 ) ) );
		$this->assertStringContainsString( 'data-id="77"', $this->render( array( 'type' => 'sxoli', 'schoolId' => 0, 'id' => '77' ) ) );
	}

	public function test_id_is_ignored_for_other_types(): void {
		$this->assertStringNotContainsString( 'data-id', $this->render( array( 'type' => 'calculator', 'id' => '5' ) ) );
	}

	public function test_unknown_or_missing_type_renders_nothing(): void {
		foreach ( array( null, '', 'Calculator', 'unknown', 'calculator ', array( 'calculator' ), 5 ) as $type ) {
			$this->assertSame( '', $this->render( array( 'type' => $type ) ) );
		}
		$this->assertSame( '', $this->render( array() ) );
		$this->assertSame( array(), $GLOBALS['panelladikes_test_enqueued'] );
	}

	public function test_invalid_values_fall_back_to_defaults(): void {
		$html = $this->render(
			array(
				'type'   => 'baseis',
				'edu'    => 'lyceum',
				'theme'  => 'neon',
				'align'  => 'middle',
				'shadow' => 'huge',
				'radius' => '49',
				'pedio'  => '5',
				'height' => '199',
				'width'  => '50px',
				'border' => 'maybe',
				'lazy'   => 'maybe',
			)
		);
		$this->assertSame( '<div data-panelladikes-widget="baseis" data-edu="gel" data-theme="auto" data-radius="12"></div>', $html );
	}

	public function test_limits_are_inclusive(): void {
		$min = $this->render( array( 'type' => 'baseis', 'pedio' => 1, 'radius' => 0, 'height' => 200, 'width' => 200 ) );
		$this->assertStringContainsString( 'data-pedio="1"', $min );
		$this->assertStringNotContainsString( 'data-radius', $min );
		$this->assertStringContainsString( 'data-height="200"', $min );
		$this->assertStringContainsString( 'data-width="200"', $min );

		$max = $this->render( array( 'type' => 'baseis', 'pedio' => 4, 'radius' => 48, 'height' => 10000, 'width' => '2000px' ) );
		$this->assertStringContainsString( 'data-pedio="4"', $max );
		$this->assertStringContainsString( 'data-radius="48"', $max );
		$this->assertStringContainsString( 'data-height="10000"', $max );
		$this->assertStringContainsString( 'data-width="2000"', $max );

		$over = $this->render( array( 'type' => 'baseis', 'pedio' => 0, 'height' => 10001, 'width' => 2001 ) );
		$this->assertStringNotContainsString( 'data-pedio', $over );
		$this->assertStringNotContainsString( 'data-height', $over );
		$this->assertStringNotContainsString( 'data-width', $over );
	}

	public function test_width_percent(): void {
		$this->assertStringContainsString( 'data-width="50%"', $this->render( array( 'type' => 'baseis', 'width' => '50%' ) ) );
		$this->assertStringNotContainsString( 'data-width', $this->render( array( 'type' => 'baseis', 'width' => '100%' ) ) );
		$this->assertStringNotContainsString( 'data-width', $this->render( array( 'type' => 'baseis', 'width' => '9%' ) ) );
		$this->assertStringNotContainsString( 'data-width', $this->render( array( 'type' => 'baseis', 'width' => '101%' ) ) );
	}

	public function test_align_needs_a_width(): void {
		$this->assertStringNotContainsString( 'data-align', $this->render( array( 'type' => 'baseis', 'align' => 'right' ) ) );
		$this->assertStringContainsString( 'data-align="right"', $this->render( array( 'type' => 'baseis', 'align' => 'right', 'width' => 400 ) ) );
		$this->assertStringNotContainsString( 'data-align', $this->render( array( 'type' => 'baseis', 'align' => 'left', 'width' => 400 ) ) );
	}

	public function test_shadow_values(): void {
		foreach ( array( 'sm', 'md', 'lg' ) as $shadow ) {
			$this->assertStringContainsString( 'data-shadow="' . $shadow . '"', $this->render( array( 'type' => 'baseis', 'shadow' => $shadow ) ) );
		}
		$this->assertStringNotContainsString( 'data-shadow', $this->render( array( 'type' => 'baseis', 'shadow' => 'none' ) ) );
	}

	public function test_boolean_forms(): void {
		foreach ( array( true, 1, '1', 'true', 'TRUE', 'yes', 'on', ' true ' ) as $yes ) {
			$this->assertStringContainsString( 'data-border="1"', $this->render( array( 'type' => 'baseis', 'border' => $yes ) ) );
		}
		foreach ( array( false, 0, '0', 'false', 'no', 'off' ) as $no ) {
			$this->assertStringNotContainsString( 'data-border', $this->render( array( 'type' => 'baseis', 'border' => $no ) ) );
			$this->assertStringContainsString( 'data-lazy="false"', $this->render( array( 'type' => 'baseis', 'lazy' => $no ) ) );
		}
	}

	/**
	 * Hostile input must never reach the output.
	 */
	public function test_hostile_input_cannot_break_out_of_attributes(): void {
		$evil = '"><script>alert(1)</script><img src=x onerror=alert(1)>';
		$html = $this->render(
			array(
				'type'     => 'sxoli',
				'id'       => '5' . $evil,
				'schoolId' => $evil,
				'edu'      => $evil,
				'theme'    => $evil,
				'align'    => $evil,
				'width'    => $evil,
				'height'   => $evil,
				'radius'   => $evil,
				'shadow'   => $evil,
				'pedio'    => $evil,
				'border'   => $evil,
				'lazy'     => $evil,
			)
		);
		$this->assertSame( '', $html, 'A school widget with a hostile id is not shown.' );

		$html = $this->render(
			array(
				'type'   => 'baseis',
				'edu'    => $evil,
				'theme'  => $evil,
				'align'  => $evil,
				'width'  => $evil,
				'height' => $evil,
				'radius' => $evil,
				'shadow' => $evil,
				'pedio'  => $evil,
				'border' => $evil,
				'lazy'   => $evil,
				'class'  => $evil,
				'onload' => 'alert(1)',
			)
		);
		$this->assertSame( '<div data-panelladikes-widget="baseis" data-edu="gel" data-theme="auto" data-radius="12"></div>', $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
		$this->assertStringNotContainsString( 'onload', $html );
	}

	public function test_type_with_markup_renders_nothing(): void {
		$this->assertSame( '', $this->render( array( 'type' => 'baseis"><script>alert(1)</script>' ) ) );
	}

	public function test_non_scalar_values_are_ignored(): void {
		$html = $this->render(
			array(
				'type'   => 'baseis',
				'edu'    => array( 'epal' ),
				'theme'  => new \stdClass(),
				'radius' => array( 5 ),
				'width'  => array( 300 ),
				'height' => 1.5e3,
				'border' => array( true ),
			)
		);
		$this->assertSame( '<div data-panelladikes-widget="baseis" data-edu="gel" data-theme="auto" data-radius="12"></div>', $html );
	}

	public function test_script_is_enqueued_once_with_the_fixed_address(): void {
		$this->render( array( 'type' => 'baseis' ) );
		$this->render( array( 'type' => 'program' ) );
		$this->render( array( 'type' => 'sxoli', 'id' => 1 ) );
		$this->assertCount( 1, $GLOBALS['panelladikes_test_enqueued'] );
		$call = $GLOBALS['panelladikes_test_enqueued']['panelladikes-widgets-embed'];
		$this->assertSame( 'https://panelladikes.edu.gr/js/embed.v2.js', $call['src'] );
		$this->assertSame( array(), $call['deps'] );
		$this->assertNull( $call['ver'] );
		$this->assertSame(
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			),
			$call['in_footer']
		);
	}

	public function test_nothing_is_enqueued_for_an_empty_render(): void {
		$this->render( array( 'type' => 'nope' ) );
		$this->assertSame( array(), $GLOBALS['panelladikes_test_enqueued'] );
	}

	public function test_iframe_mode_uses_the_same_attribute_set(): void {
		$html = $this->render(
			array(
				'type'   => 'sxoli',
				'edu'    => 'gel',
				'id'     => 42,
				'theme'  => 'light',
				'pedio'  => 2,
				'width'  => 500,
				'align'  => 'center',
				'height' => 640,
				'border' => true,
				'shadow' => 'md',
				'radius' => 10,
			),
			'iframe'
		);
		$this->assertStringStartsWith( '<iframe src="https://panelladikes.edu.gr/embed/v2/sxoli/42?edu=gel&amp;theme=light&amp;pedio=2&amp;h=fixed"', $html );
		$this->assertStringContainsString( ' title="Στοιχεία σχολής ΓΕΛ, από το panelladikes.edu.gr"', $html );
		$this->assertStringContainsString( ' loading="lazy" referrerpolicy="origin"', $html );
		$this->assertStringContainsString( 'width:500px;max-width:100%;height:640px;min-height:0px;', $html );
		$this->assertStringContainsString( 'border:1px solid rgba(128,128,128,.35);border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.18);margin-left:auto;margin-right:auto;', $html );
		$this->assertStringNotContainsString( ' allow=', $html );
		$this->assertArrayNotHasKey( 'panelladikes-widgets-embed', $GLOBALS['panelladikes_test_enqueued'] );
	}

	public function test_iframe_mode_auto_height_uses_the_minimum(): void {
		$html = $this->render( array( 'type' => 'program' ), 'iframe' );
		$this->assertStringContainsString( 'embed/v2/program?edu=gel&amp;theme=auto&amp;h=auto', $html );
		$this->assertStringContainsString( 'height:480px;min-height:480px;', $html );
	}

	public function test_iframe_mode_grants_clipboard_only_to_mixanografiko(): void {
		$this->assertStringContainsString( ' allow="clipboard-write; web-share"', $this->render( array( 'type' => 'mixanografiko' ), 'iframe' ) );
		$this->assertStringNotContainsString( ' allow=', $this->render( array( 'type' => 'calculator' ), 'iframe' ) );
	}

	public function test_iframe_mode_school_without_id_renders_nothing(): void {
		$this->assertSame( '', $this->render( array( 'type' => 'sxoli' ), 'iframe' ) );
	}

	public function test_iframe_mode_escapes_hostile_input(): void {
		$html = $this->render( array( 'type' => 'baseis', 'theme' => '"><script>x</script>', 'width' => '"><b>' ), 'iframe' );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	public function test_unknown_mode_is_the_div(): void {
		$this->assertStringStartsWith( '<div ', $this->render( array( 'type' => 'baseis' ), 'object' ) );
	}
}
