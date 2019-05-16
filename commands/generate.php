<?php
namespace PNODEV\CLI\Command;

use WP_CLI;

/**
 * Manage data generation commands.
 */
class Generate extends PNOCommand {

	/**
	 * Generate profile fields.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp pno generate profile_fields
	 */
	public function profile_fields() {

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

		parent::delete_profile_fields();

		$field_priority = 100;

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating random profile fields', count( $available_profile_fields ) );

		foreach ( $available_profile_fields as $type => $name ) {

			$field_priority++;

			$new_field = \PNO\Entities\Field\Profile::create(
				[
					'name'     => 'Test ' . $name . ' field',
					'priority' => $field_priority,
					'type'     => $type,
				]
			);

			$this->generate_field_settings( 'profile', $new_field );

			$notify->tick();

		}

		\PNO\Cache\Helper::flush_all_fields_cache();

		$notify->finish();

		$this->generate_registration_fields();

		$this->generate_users_data();

	}

	/**
	 * Automatically assign settings to generated fields.
	 *
	 * @param string $type type of field being generated.
	 * @param object $field field entity.
	 * @return void
	 */
	private function generate_field_settings( $type, $field ) {

		// Assign a description.
		$desc = \Faker\Provider\Lorem::sentence( 6, true );

		carbon_set_post_meta( $field->getPostID(), 'profile_field_description', $desc );

		// Assign a placeholder.
		$placeholder = \Faker\Provider\Lorem::sentence( 6, true );

		carbon_set_post_meta( $field->getPostID(), 'profile_field_placeholder', $placeholder );

		// Assign options to dropdowns and radios.
		if ( in_array( $field->getType(), pno_get_multi_options_field_types() ) ) {

			// Assign a placeholder.
			$options = \Faker\Provider\Lorem::words( 3, false );

			$formatted = [];

			foreach ( $options as $option ) {
				$formatted[] = [ 'option_title' => $option ];
			}

			carbon_set_post_meta( $field->getPostID(), 'profile_field_selectable_options', $formatted );

		}

	}

	/**
	 * Generate registration fields for all the newly generated profile fields.
	 *
	 * @return void
	 */
	private function generate_registration_fields() {

		// Delete any previously created registration field.
		parent::delete_registration_fields();

		$fields = new \PNO\Database\Queries\Profile_Fields(
			[
				'user_meta_key__not_in' => pno_get_registered_default_meta_keys(),
				'number'                => 999,
			]
		);

		if ( ! empty( $fields->items ) && is_array( $fields->items ) ) {

			$notify = \WP_CLI\Utils\make_progress_bar( 'Generating associated registration fields', count( $fields->items ) );

			foreach ( $fields->items as $profile_field ) {

				if ( $profile_field->getType() === 'file' ) {
					continue;
				}

				$new_registration_field = \PNO\Entities\Field\Registration::create(
					[
						'name'             => $profile_field->getTitle(),
						'profile_field_id' => $profile_field->getPostID(),
						'priority'         => $profile_field->getPriority(),
					]
				);

				$notify->tick();

			}

			$notify->finish();

		}

	}

	/**
	 * Generate random user's data for all fields generated.
	 *
	 * @return void
	 */
	private function generate_users_data() {

		$fields = new \PNO\Database\Queries\Profile_Fields(
			[
				'user_meta_key__not_in' => pno_get_registered_default_meta_keys(),
				'number'                => 999,
			]
		);

		$users = new \WP_User_Query(
			[
				'number' => -1,
				'fields' => 'ID',
			]
		);

		if ( ! empty( $fields->items ) && is_array( $fields->items ) ) {

			$notify = \WP_CLI\Utils\make_progress_bar( 'Generating user data for the profile fields.', count( $fields->items ) );

			foreach ( $fields->items as $profile_field ) {

				if ( $profile_field->getType() === 'file' ) {
					continue;
				}

				$type     = $profile_field->getType();
				$meta_key = $profile_field->getObjectMetaKey();

				switch ( $type ) {
					case 'url':
					case 'email':
					case 'password':
					case 'text':
						$text = \Faker\Provider\Lorem::sentence( 10, true );
						foreach ( $users->get_results() as $user_id ) {
							carbon_set_user_meta( $user_id, $meta_key, $text );
						}
						break;
					case 'editor':
					case 'textarea':
						$text = \Faker\Provider\Lorem::paragraphs( 2, true );
						foreach ( $users->get_results() as $user_id ) {
							carbon_set_user_meta( $user_id, $meta_key, $text );
						}
						break;
					case 'select':
					case 'multiselect':
					case 'multicheckbox':
						$options = $profile_field->getOptions();
						$options = array_rand( $options, 2 );
						foreach ( $users->get_results() as $user_id ) {
							carbon_set_user_meta( $user_id, $meta_key, $options );
						}
						break;
					case 'radio':
						$options = $profile_field->getOptions();
						foreach ( $users->get_results() as $user_id ) {
							carbon_set_user_meta( $user_id, $meta_key, key( array_slice( $options, 1, 1, true ) ) );
						}
						break;
					case 'checkbox':
						foreach ( $users->get_results() as $user_id ) {
							carbon_set_user_meta( $user_id, $meta_key, true );
						}
						break;
					case 'number':
						foreach ( $users->get_results() as $user_id ) {
							carbon_set_user_meta( $user_id, $meta_key, \Faker\Provider\Base::randomNumber() );
						}
						break;
				}

				$notify->tick();

			}

			$notify->finish();

		}

	}

	/**
	 * Generate taxonomy terms.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp pno generate taxonomies
	 */
	public function taxonomies() {

		parent::delete_taxonomies();

	}

}
