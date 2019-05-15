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

		$field = new \PNO\Database\Queries\Profile_Fields(
			[
				'user_meta_key__not_in' => pno_get_registered_default_meta_keys(),
				'number'                => 999,
			]
		);

		$notify = \WP_CLI\Utils\make_progress_bar( 'Deleting all non default profile fields.', count( $field->items ) );

		foreach ( $field->items as $found_field ) {
			if ( $found_field instanceof \PNO\Entities\Field\Profile && $found_field->getPostID() > 0 && $found_field->canDelete() ) {
				$found_field::delete( $found_field->getPostID() );
			}

			$notify->tick();

		}

		\PNO\Cache\Helper::flush_all_fields_cache();

		$notify->finish();

	}

}
