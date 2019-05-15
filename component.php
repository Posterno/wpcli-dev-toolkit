<?php
namespace PNODEV\CLI\Command;

use WP_CLI;
use WP_CLI\CommandWithDBObject;

/**
 * Base component class.
 *
 * @since 1.0
 */
abstract class PNOCommand extends CommandWithDBObject {

	/**
	 * Get a random user id.
	 *
	 * @return int
	 */
	protected function get_random_user_id() {
		global $wpdb;
		return $wpdb->get_var( "SELECT ID FROM $wpdb->users ORDER BY RAND() LIMIT 1" );
	}

	/**
	 * Verify a user ID by the passed identifier.
	 *
	 * @param mixed $i User ID, email or login.
	 * @return WP_User|false
	 */
	protected function get_user_id_from_identifier( $i ) {
		if ( is_numeric( $i ) ) {
			$user = get_user_by( 'id', $i );
		} elseif ( is_email( $i ) ) {
			$user = get_user_by( 'email', $i );
		} else {
			$user = get_user_by( 'login', $i );
		}

		if ( ! $user ) {
			WP_CLI::error( sprintf( 'No user found by that username or ID (%s).', $i ) );
		}

		return $user;
	}

	/**
	 * String Sanitization.
	 *
	 * @param  string $type String to sanitize.
	 * @return string Sanitized string.
	 */
	protected function sanitize_string( $type ) {
		return strtolower( str_replace( '-', '_', $type ) );
	}

	/**
	 * Helper function delete generated profile fields.
	 *
	 * @return void
	 */
	protected function delete_profile_fields() {

		$available_profile_fields = pno_get_registered_field_types(
			[
				'social-profiles',
				'listing-category',
				'listing-tags',
				'term-select',
				'term-multiselect',
				'term-checklist',
				'term-chain-dropdown',
				'listing-opening-hours',
				'listing-location',
			]
		);

		$notify = \WP_CLI\Utils\make_progress_bar( 'Deleting previously generated profile fields.', count( $available_profile_fields ) );

		$field = new \PNO\Database\Queries\Profile_Fields();

		foreach ( $available_profile_fields as $type => $name ) {

			$name = strtolower( str_replace( '-', '_', $name ) );

			$found_field = $field->get_item_by( 'user_meta_key', $name );

			if ( $found_field instanceof \PNO\Entities\Field\Profile && $found_field->getPostID() > 0 && $found_field->canDelete() ) {
				$found_field::delete( $found_field->getPostID() );
			}

			$notify->tick();

		}

		\PNO\Cache\Helper::flush_all_fields_cache();

		$notify->finish();

	}

}
