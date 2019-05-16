<?php

namespace PNODEV\CLI\Taxonomies;

add_action(
	'init',
	function() {

		$labels = array(
			'name'          => 'Taxonomy 1',
			'singular_name' => 'Taxonomy 1',
			'menu_name'     => 'Taxonomy 1',
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'taxonomy1', array( 'listings' ), $args );

		$labels = array(
			'name'          => 'Taxonomy 2',
			'singular_name' => 'Taxonomy 2',
			'menu_name'     => 'Taxonomy 2',
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'taxonomy2', array( 'listings' ), $args );

		$labels = array(
			'name'          => 'Taxonomy 3',
			'singular_name' => 'Taxonomy 3',
			'menu_name'     => 'Taxonomy 3',
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'taxonomy3', array( 'listings' ), $args );

		$labels = array(
			'name'          => 'Taxonomy 4',
			'singular_name' => 'Taxonomy 4',
			'menu_name'     => 'Taxonomy 4',
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'taxonomy4', array( 'listings' ), $args );

	}
);
