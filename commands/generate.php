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

		carbon_set_post_meta( $field->getPostID(), "{$type}_field_description", $desc );

		// Assign a placeholder.
		$placeholder = \Faker\Provider\Lorem::sentence( 6, true );

		carbon_set_post_meta( $field->getPostID(), "{$type}_field_placeholder", $placeholder );

		// Assign options to dropdowns and radios.
		if ( in_array( $field->getType(), pno_get_multi_options_field_types() ) ) {

			// Assign a placeholder.
			$options = \Faker\Provider\Lorem::words( 3, false );

			$formatted = [];

			foreach ( $options as $option ) {
				$formatted[] = [ 'option_title' => $option ];
			}

			carbon_set_post_meta( $field->getPostID(), "{$type}_field_selectable_options", $formatted );

		} elseif ( in_array( $field->getType(), [ 'term-select', 'term-multiselect', 'term-checklist', 'term-chain-dropdown' ] ) ) {

			if ( $field->getType() === 'term-chain-dropdown' ) {
				carbon_set_post_meta( $field->getPostID(), 'listing_field_taxonomy', 'taxonomy4' );
			} elseif ( $field->getType() === 'term-select' ) {
				carbon_set_post_meta( $field->getPostID(), 'listing_field_taxonomy', 'taxonomy1' );
			} elseif ( $field->getType() === 'term-multiselect' ) {
				carbon_set_post_meta( $field->getPostID(), 'listing_field_taxonomy', 'taxonomy2' );
			} elseif ( $field->getType() === 'term-checklist' ) {
				carbon_set_post_meta( $field->getPostID(), 'listing_field_taxonomy', 'taxonomy3' );
			}
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

		global $wpdb;

		parent::delete_taxonomies();

		$taxonomies = get_object_taxonomies( 'listings' );

		$amount = 12;

		foreach ( $taxonomies as $tax ) {

			switch ( $tax ) {
				case 'taxonomy1':
				case 'taxonomy2':
					$notify = \WP_CLI\Utils\make_progress_bar( 'Generating terms for ' . $tax, count( $amount ) );
					foreach ( range( 1, $amount ) as $index ) {
						wp_insert_term( \Faker\Provider\Base::lexify( 'Term ?????' ), $tax );
						$notify->tick();
					}
					$notify->finish();
					break;
				case 'listings-types':
					$types_amount = 3;
					$notify       = \WP_CLI\Utils\make_progress_bar( 'Generating terms for ' . $tax, count( $types_amount ) );
					foreach ( range( 1, $types_amount ) as $index ) {
						wp_insert_term( \Faker\Provider\Base::numerify( 'Type ###' ), $tax );
						$notify->tick();
					}
					$notify->finish();
					break;
				case 'listings-locations':
					$cats_amount = 30;
					$notify      = \WP_CLI\Utils\make_progress_bar( 'Generating terms for ' . $tax, count( $cats_amount ) );
					foreach ( range( 1, $cats_amount ) as $index ) {
						wp_insert_term( \Faker\Provider\en_US\Address::country(), $tax );
						$notify->tick();
					}
					$notify->finish();

					// Randomize hierarchy.
					$terms = get_terms(
						[
							'taxonomy'   => $tax,
							'hide_empty' => false,
							'number'     => 9999,
						]
					);

					$random_terms = \Faker\Provider\Base::randomElements( $terms, 3 );

					foreach ( $random_terms as $term ) {

						$cats_amount = 5;
						$notify      = \WP_CLI\Utils\make_progress_bar( 'Generating random child terms for ' . $term->name, count( $cats_amount ) );

						foreach ( range( 1, $cats_amount ) as $index ) {
							wp_insert_term( \Faker\Provider\Base::numerify( 'Child Type ###' ), $tax, [ 'parent' => $term->term_id ] );
							$notify->tick();
						}
						$notify->finish();

					}
					break;
				default:
					$cats_amount = 20;
					$notify      = \WP_CLI\Utils\make_progress_bar( 'Generating terms for ' . $tax, count( $cats_amount ) );
					foreach ( range( 1, $cats_amount ) as $index ) {
						wp_insert_term( \Faker\Provider\Base::numerify( 'Type ###' ), $tax );
						$notify->tick();
					}
					$notify->finish();

					if ( is_taxonomy_hierarchical( $tax ) ) {
						// Randomize hierarchy.
						$terms = get_terms(
							[
								'taxonomy'   => $tax,
								'hide_empty' => false,
								'number'     => 9999,
							]
						);

						$random_terms = \Faker\Provider\Base::randomElements( $terms, 3 );

						foreach ( $random_terms as $term ) {

							$cats_amount = 5;
							$notify      = \WP_CLI\Utils\make_progress_bar( 'Generating random child terms for ' . $term->name, count( $cats_amount ) );

							foreach ( range( 1, $cats_amount ) as $index ) {
								wp_insert_term( \Faker\Provider\Base::numerify( 'Child Type ###' ), $tax, [ 'parent' => $term->term_id ] );
								$notify->tick();
							}
							$notify->finish();

						}
					}
					break;
			}
		}

	}

	/**
	 * Generate listings fields.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp pno generate listings_fields
	 */
	public function listings_fields() {

		$available_fields = pno_get_registered_field_types(
			[
				'social-profiles',
				'listing-category',
				'listing-tags',
				'listing-opening-hours',
				'listing-location',
			]
		);

		parent::delete_listings_fields();

		$field_priority = 100;

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating random listings fields', count( $available_fields ) );

		foreach ( $available_fields as $type => $name ) {

			$field_priority++;

			$new_field = \PNO\Entities\Field\Listing::create(
				[
					'name'     => 'Test ' . $name . ' field',
					'priority' => $field_priority,
					'type'     => $type,
				]
			);

			$this->generate_field_settings( 'listing', $new_field );

			$notify->tick();

		}

		\PNO\Cache\Helper::flush_all_fields_cache();

		$notify->finish();

		$this->generate_listings_data();

	}

	/**
	 * Generate data for all custom listings fields.
	 *
	 * @return void
	 */
	private function generate_listings_data() {

		$not_needed = [
			'listing_title',
			'listing_description',
			'listing_opening_hours',
			'listing_featured_image',
			'listing_gallery',
			'listing_location',
			'listing_categories',
			'listing_tags',
			'listing_regions',
			'listing_video',
		];

		$fields = new \PNO\Database\Queries\Listing_Fields(
			[
				'listing_meta_key__not_in' => $not_needed,
				'number'                   => 999,
			]
		);

		$listings = new \WP_Query(
			[
				'post_type' => 'listings',
				'number'    => -1,
				'fields'    => 'ids',
			]
		);

		if ( ! empty( $fields->items ) && is_array( $fields->items ) ) {

			$notify = \WP_CLI\Utils\make_progress_bar( 'Generating user data for the listing fields.', count( $fields->items ) );

			foreach ( $fields->items as $listing_field ) {

				if ( $listing_field->getType() === 'file' ) {
					continue;
				}

				$type     = $listing_field->getType();
				$meta_key = $listing_field->getObjectMetaKey();

				switch ( $type ) {
					case 'url':
					case 'email':
					case 'password':
					case 'text':
						$text = \Faker\Provider\Lorem::sentence( 10, true );
						foreach ( $listings->get_posts() as $post_id ) {
							carbon_set_post_meta( $post_id, $meta_key, $text );
						}
						break;
					case 'editor':
					case 'textarea':
						$text = \Faker\Provider\Lorem::paragraphs( 2, true );
						foreach ( $listings->get_posts() as $post_id ) {
							carbon_set_post_meta( $post_id, $meta_key, $text );
						}
						break;
					case 'select':
					case 'multiselect':
					case 'multicheckbox':
						$options = $listing_field->getOptions();
						$options = array_rand( $options, 2 );
						foreach ( $listings->get_posts() as $post_id ) {
							carbon_set_post_meta( $post_id, $meta_key, $options );
						}
						break;
					case 'radio':
						$options = $listing_field->getOptions();
						foreach ( $listings->get_posts() as $post_id ) {
							carbon_set_post_meta( $post_id, $meta_key, key( array_slice( $options, 1, 1, true ) ) );
						}
						break;
					case 'checkbox':
						foreach ( $listings->get_posts() as $post_id ) {
							carbon_set_post_meta( $post_id, $meta_key, true );
						}
						break;
					case 'number':
						foreach ( $listings->get_posts() as $post_id ) {
							carbon_set_post_meta( $post_id, $meta_key, \Faker\Provider\Base::randomNumber() );
						}
						break;
					case 'term-select':
					case 'term-multiselect':
					case 'term-checklist':
					case 'term-chain-dropdown':
						$tax = $listing_field->getTaxonomy();

						// Randomize hierarchy.
						$terms = get_terms(
							[
								'taxonomy'   => $tax,
								'hide_empty' => false,
								'number'     => 9999,
							]
						);

						$random_terms = \Faker\Provider\Base::randomElements( $terms, 3 );

						foreach ( $listings->get_posts() as $post_id ) {
							$termslist = [];
							foreach ( $random_terms as $term ) {
								$termslist[] = $term->term_id;
							}
							wp_set_post_terms( $post_id, $termslist, $tax );
						}
						break;
				}

				$notify->tick();

			}

			$notify->finish();

		}

	}
}
