<?php
/**
 * Fixed settings of the updater.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Updater;

defined( 'ABSPATH' ) || exit;

/**
 * Constants. Nothing here is read from options or filters.
 */
final class Config {

	const SLUG = 'panelladikes-widgets';

	/**
	 * Address of the update description. Must match the `Update URI` header.
	 */
	const FEED_URL = 'https://github.com/PanelladikesApp/panelladikes-wordpress/releases/latest/download/update.json';

	/**
	 * Path every release package must live under.
	 */
	const PACKAGE_PATH_PREFIX = '/PanelladikesApp/panelladikes-wordpress/releases/download/';

	/**
	 * Hosts the update description and the package may come from, including redirects.
	 */
	const ALLOWED_HOSTS = array(
		'github.com',
		'objects.githubusercontent.com',
		'release-assets.githubusercontent.com',
	);

	/**
	 * Ed25519 public key that release packages are signed with: 32 bytes, base64.
	 *
	 * While this is empty or not a valid key, no update is offered and no package is accepted.
	 */
	const PUBLIC_KEY = 'zDkhmd0GpYVXl9lFMwxcr1eIICMLwnBeRohDtAzGKIU=';

	/**
	 * Shape of a package address: one release asset of this repository, a single file name, nothing else.
	 */
	const PACKAGE_PATTERN = '#^https://github\.com/PanelladikesApp/panelladikes-wordpress/releases/download/v\d+\.\d+\.\d+/[A-Za-z0-9._-]+\.zip$#D';

	const PACKAGE_MAX_BYTES = 10485760;
	const FEED_TTL          = 43200;
	const FEED_FAIL_TTL     = 3600;
	const FEED_MAX_BYTES    = 65536;
	const FEED_TIMEOUT      = 5;
	const PACKAGE_TIMEOUT   = 60;
	const MAX_REDIRECTS     = 4;
	const TRANSIENT         = 'panelladikes_widgets_update_feed';
}
