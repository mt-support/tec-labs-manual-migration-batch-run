<?php
/**
 * Handles processing a batch of Event Migrations for The Events Calendar 6.0
 *
 * @since   1.0.0
 *
 * @package Tribe\Extensions\Manual_Batch_Upgrade_6;
 */

namespace Tribe\Extensions\Manual_Batch_Upgrade_6;

use TEC\Events\Custom_Tables\V1\Migration\Events;
use TEC\Events\Custom_Tables\V1\Migration\Process;
use TEC\Events\Custom_Tables\V1\Migration\Process_Worker;
use TEC\Events\Custom_Tables\V1\Migration\Reports\Event_Report;
use TEC\Events\Custom_Tables\V1\Migration\State;
use WP_Post;

/**
 * Class Process_Migration.
 *
 * @since   1.0.0
 *
 * @package Tribe\Extensions\Manual_Batch_Upgrade_6;
 */
class Process_Migration {

	const RUN_ACTION = 'tec_ct1_manually_run_migration_batch';
	const RUN_NONCE = 'tec_ct1_manually_run_migration_batch_nonce';
	const REQUEST_BATCH_COUNT_PARAM = 'tec_ct1_manually_run_migration_batch_count';

	/**
	 * Output the run migration form.
	 *
	 * @since 1.0.0
	 */
	public function inject_form() {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}
		$state = tribe( State::class );
		if ( ! $state->is_migrated() ) {
			include_once __DIR__ . '/../admin-views/form.php';
		}
	}

	/**
	 * Clears out any locked events that likely were initiated for an Action Scheduler queue.
	 * Synchronous actions will be locked and unlocked in one thread.
	 *
	 * @since 1.0.0
	 */
	protected function clear_locked_events() {
		global $wpdb;
		$process = tribe( Process::class );
		// No more AS in queue now.
		$process->empty_process_queue();
		// Fetch any that are currently locked - clear lock and let them be processed as normal.
		$fetch_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s ";
		$fetch_query = $wpdb->prepare( $fetch_query, Event_Report::META_KEY_MIGRATION_LOCK_HASH );
		$ids         = $wpdb->get_col( $fetch_query );
		foreach ( $ids as $post_id ) {
			$post = get_post( $post_id );
			// In case it was deleted or something else happened.
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$event_report = new Event_Report( $post );
			$event_report->clear_meta();
		}
	}

	/**
	 * Migrates a batch of events from a single request.
	 *
	 * @since 1.0.0
	 */
	public function run_batch_from_request() {
		if ( ! current_user_can( 'administrator' ) && ! wp_verify_nonce( Hooks::RUN_NONCE ) ) {
			return;
		}

		$state             = tribe( State::class );
		$events_repository = tribe( Events::class );
		$process           = tribe( Process::class );

		// If we are already migrated, quit - nothing to do.
		if ( $state->is_migrated() ) {
			return;
		}

		// In progress?
		if ( $state->is_running() ) {
			// Make sure we clear our queue if there were some rogues.
			$process->empty_process_queue();
		}

		// Check if we have any locked. These will be items locked via Action Scheduler,
		// so let's unlock them and put them in the queue of items we can process synchronously below.
		if ( $events_repository->get_total_events_in_progress() > 0 ) {
			$this->clear_locked_events();
		}

		// Manually align migration state.
		// Basically, if we are in a "start" prompt, move into the in progress phase.
		// Otherwise, default back to the preview prompt.
		switch ( $state->get_phase() ) {
			case State::PHASE_MIGRATION_PROMPT:
				$state->set( 'phase', State::PHASE_MIGRATION_IN_PROGRESS );
				$state->save();
				break;
			case State::PHASE_PREVIEW_IN_PROGRESS:
			case State::PHASE_MIGRATION_IN_PROGRESS:
				break;
			// Several migration phases, let's push us into this beginning phase just in case
			default:
				$state->set( 'phase', State::PHASE_PREVIEW_IN_PROGRESS );
				$state->save();
				break;
		}

		// Check if real migration or not.
		$dry_run = $state->get_phase() !== State::PHASE_MIGRATION_IN_PROGRESS;

		// Run migration loop - hard cap how many ID's we grab at once.
		$posts = $events_repository->get_ids_to_process( min( 50, 250 ) );
		foreach ( $posts as $post_id ) {
			tribe( Process_Worker::class )->migrate_event( $post_id, $dry_run );
		}
		tribe( Process_Worker::class )->check_phase();
		// Force back to settings tab
		$settings_page = admin_url( 'edit.php?post_type=tribe_events&page=tec-events-settings&tab=upgrade' );
		wp_redirect( $settings_page );
	}
}
