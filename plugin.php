<?php
/**
 * Plugin Name:       The Events Calendar Extension: Manual Batch Run Upgrade to 6.0
 * Plugin URI:        @todo
 * GitHub Plugin URI: https://github.com/mt-support/tec-labs-manual-migration-batch-run
 * Description:       Adds a form to allow manually running a batch of events through migration to the 6.0 custom tables implementation. This sidesteps the Action Scheduler queue, in cases where Action Scheduler is not running the migration queue.
 * Version:           1.0.1
 * Author:            The Events Calendar
 * Author URI:        https://evnt.is/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tec-labs-manual-batch-upgrade-to-6
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

/**
 * Define the base file that loaded the plugin for determining plugin path and other variables.
 *
 * @since 1.0.0
 *
 * @var string Base file that loaded the plugin.
 */
define( 'TRIBE_EXTENSION___TRIBE_SLUG_CLEAN_UPPERCASE___FILE', __FILE__ );

/**
 * Register and load the service provider for loading the extension.
 *
 * @since 1.0.0
 */
function tribe_extension_tec_labs_manual_batch_upgrade_to_6() {
	// When we don't have autoloader from common we bail.
	if ( ! class_exists( 'Tribe__Autoloader' ) ) {
		return;
	}

	// Register the namespace so we can the plugin on the service provider registration.
	Tribe__Autoloader::instance()->register_prefix(
		'\\Tribe\\Extensions\\Manual_Batch_Upgrade_6\\',
		__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Tec',
		'manual-batch-upgrade-to-6'
	);

	// Deactivates the plugin in case of the main class didn't autoload.
	if ( ! class_exists( '\Tribe\Extensions\Manual_Batch_Upgrade_6\Plugin' ) ) {
		tribe_transient_notice(
			'manual-batch-upgrade-to-6',
			'<p>' . esc_html__( 'Couldn\'t properly load "The Events Calendar Extension: Manual Batch Run Upgrade to 6.0" the extension was deactivated.', 'tec-labs-manual-batch-upgrade-to-6' ) . '</p>',
			[],
			// 1 second after that make sure the transient is removed.
			1
		);

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		deactivate_plugins( __FILE__, true );
		return;
	}

	tribe_register_provider( '\Tribe\Extensions\Manual_Batch_Upgrade_6\Plugin' );
}

// Loads after common is already properly loaded.
add_action( 'tribe_common_loaded', 'tribe_extension_tec_labs_manual_batch_upgrade_to_6' );
