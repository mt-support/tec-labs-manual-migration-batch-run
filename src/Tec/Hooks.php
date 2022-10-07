<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * ```php
 *  remove_filter( 'some_filter', [ tribe( Tribe\Extensions\Manual_Batch_Upgrade_6\Hooks::class ),
 *  'some_filtering_method' ] ); remove_filter( 'some_filter', [ tribe(
 *  'extension.tec_labs_manual_batch_upgrade_to_6.hooks' ), 'some_filtering_method' ] );
 * ```
 *
 * To remove an action:
 * ```php
 *  remove_action( 'some_action', [ tribe( Tribe\Extensions\Manual_Batch_Upgrade_6\Hooks::class ), 'some_method' ] );
 *  remove_action( 'some_action', [ tribe( 'extension.tec_labs_manual_batch_upgrade_to_6.hooks' ), 'some_method' ] );
 * ```
 *
 * @since   1.0.0
 *
 * @package Tribe\Extensions\Manual_Batch_Upgrade_6;
 */

namespace Tribe\Extensions\Manual_Batch_Upgrade_6;

use TEC\Events\Custom_Tables\V1\Migration\State;
use Tribe__Main as Common;

/**
 * Class Hooks.
 *
 * @since   1.0.0
 *
 * @package Tribe\Extensions\Manual_Batch_Upgrade_6;
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.tec_labs_manual_batch_upgrade_to_6.hooks', $this );

		$this->add_actions();
	}

	/**
	 * Adds the actions required by the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function add_actions() {
		add_action( 'tribe_load_text_domains', [ $this, 'load_text_domains' ] );

		if ( is_admin() && class_exists( State::class ) ) {
			add_action( 'tribe_settings_after_form_element_tab_upgrade', [ $this, 'inject_form' ] );
			add_action( 'admin_post_' . Process_Migration::RUN_ACTION, [ $this, 'run_batch_from_request' ] );

		}
	}

	/**
	 * Outputs the migration form.
	 *
	 * @since 1.0.0
	 */
	public function inject_form() {
		$this->container->make( Process_Migration::class )->inject_form();
	}

	/**
	 * Handles migrating a batch of events.
	 *
	 * @since 1.0.0
	 */
	public function run_batch_from_request() {
		$this->container->make( Process_Migration::class )->run_batch_from_request();
	}

	/**
	 * Load text domain for localization of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_text_domains() {
		$mopath = tribe( Plugin::class )->plugin_dir . 'lang/';
		$domain = Plugin::TEXT_DOMAIN;

		// This will load `wp-content/languages/plugins` files first.
		Common::instance()->load_text_domain( $domain, $mopath );
	}
}
