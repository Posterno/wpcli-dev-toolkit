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
	 *     Posterno: 3.0.0
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

}
