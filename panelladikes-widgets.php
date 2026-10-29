<?php
/**
 * Plugin Name:       Panelladikes Widgets
 * Plugin URI:        https://github.com/PanelladikesApp/panelladikes-wordpress
 * Description:       Embed the Panelladikes widgets (points calculator, admission bases, exam schedule, school details, Mixanografiko) with a block or a shortcode.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            Panelladikes
 * Author URI:        https://panelladikes.edu.gr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       panelladikes-widgets
 * Domain Path:       /languages
 * Update URI:        https://github.com/PanelladikesApp/panelladikes-wordpress/releases/latest/download/update.json
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets;

defined( 'ABSPATH' ) || exit;

const VERSION     = '1.0.0';
const PLUGIN_FILE = __FILE__;

require_once __DIR__ . '/includes/class-contract.php';
require_once __DIR__ . '/includes/class-normalizer.php';
require_once __DIR__ . '/includes/class-renderer.php';
require_once __DIR__ . '/includes/class-shortcode.php';
require_once __DIR__ . '/includes/class-block.php';
require_once __DIR__ . '/includes/class-privacy.php';

Shortcode::register();
Block::register();
Privacy::register();

require_once __DIR__ . '/includes/updater/bootstrap.php';
