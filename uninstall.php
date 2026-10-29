<?php
/**
 * Removes what the plugin leaves in the database when it is deleted.
 *
 * The only thing is the cached description of the latest release, kept as a
 * site transient (network-wide on multisite, so one call is enough).
 *
 * @package Panelladikes_Widgets
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Same name as Config::TRANSIENT in includes/updater/, written out so this file does not depend on the updater.
delete_site_transient( 'panelladikes_widgets_update_feed' );
