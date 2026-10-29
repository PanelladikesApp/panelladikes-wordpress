<?php
/**
 * Self-hosted updates from GitHub releases.
 *
 * This folder is self-contained: the plugin works the same without it, and
 * removing it (with the single `require_once` that loads this file and the
 * `Update URI` header) removes every update-related behaviour.
 *
 * @package Panelladikes_Widgets
 */

namespace Panelladikes\Widgets\Updater;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-config.php';
require_once __DIR__ . '/class-http.php';
require_once __DIR__ . '/class-feed.php';
require_once __DIR__ . '/class-verifier.php';
require_once __DIR__ . '/class-updater.php';

Updater::register();
