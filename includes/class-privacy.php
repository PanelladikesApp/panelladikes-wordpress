<?php
/**
 * Suggested text for the site's privacy policy.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a section to Settings > Privacy > Policy Guide.
 */
final class Privacy {

	/**
	 * Hook the policy text into WordPress.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_init', array( __CLASS__, 'add_content' ) );
	}

	/**
	 * Offer the suggested text.
	 *
	 * @return void
	 */
	public static function add_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		wp_add_privacy_policy_content( 'Panelladikes Widgets', self::content() );
	}

	/**
	 * The suggested text as HTML.
	 *
	 * @return string
	 */
	public static function content(): string {
		$paragraphs = array(
			__( 'Pages of this site that contain a Panelladikes widget load a script and the widget itself from panelladikes.edu.gr. Your browser contacts panelladikes.edu.gr directly, which receives the usual details of a web request (for example your IP address and browser) and the address (origin) of the site you are visiting, and uses it to apply the settings of that site. It counts views per site, widget type and school system, without your IP address, browser details, the full page address or any identifier of you. This site itself stores no data about it.', 'panelladikes-widgets' ),
			__( 'If a page contains the Mixanografiko widget, the list of schools you build is kept in the local storage of your browser for panelladikes.edu.gr, not for this site. The widget can send the list to your email address only when the service has turned this on. In that case the form sends the list of schools, the school system, the field or sector, the email address you enter, your language and, only if you tick a separate option, a newsletter subscription together with the version of the consent text. No name or grades are sent. Your browser sends this directly to the database of panelladikes.edu.gr (Google Firebase), protected by Google App Check and reCAPTCHA Enterprise, which load scripts from Google. The data does not pass through this site and is not sent to its owner. After sending, only a masked email address is kept in your browser. The widget itself explains how the email address is used.', 'panelladikes-widgets' ),
			__( 'The plugin sets no cookies and runs no analytics. The Google services of the email form of the Mixanografiko widget (reCAPTCHA, App Check) may set cookies or use local storage. As a rule the script and the widgets are loaded from panelladikes.edu.gr; if the appearance of a site has been customized, images, fonts or stylesheets may also be loaded from other sources. The plugin creates no options and keeps only one temporary transient of its update check, which is removed when the plugin is deleted.', 'panelladikes-widgets' ),
		);
		return wp_kses_post( '<p>' . implode( '</p><p>', $paragraphs ) . '</p>' );
	}
}
