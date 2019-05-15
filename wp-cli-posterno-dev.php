<?php
/*
Plugin Name: wp-cli-posterno-dev
Plugin URI:  https://posterno.com
Description: WP-CLI toolkit to speed up testing of Posterno.
Version: 1.0.0
Author:      Alessandro Tesoro
Author URI:  https://alessandrotesoro.me
License:     GPLv2+
*/

namespace PNODEV\CLI;

use WP_CLI;

// Bail if WP-CLI is not present.
if ( ! defined( '\WP_CLI' ) ) {
	return;
}

WP_CLI::add_hook(
	'before_wp_load',
	function() {
		require_once __DIR__ . '/component.php';
		require_once __DIR__ . '/commands/posterno.php';
		require_once __DIR__ . '/commands/tool.php';

		WP_CLI::add_command(
			'pno',
			__NAMESPACE__ . '\\Command\\Posterno',
			array(
				'before_invoke' => function() {
					if ( ! class_exists( 'Posterno' ) ) {
						WP_CLI::error( 'The Posterno plugin is not active.' );
					}
				},
			)
		);

		WP_CLI::add_command(
			'pno tool',
			__NAMESPACE__ . '\\Command\\Tool',
			array(
				'before_invoke' => function() {
					if ( ! class_exists( 'Posterno' ) ) {
						WP_CLI::error( 'The Posterno plugin is not active.' );
					}
				},
			)
		);

	}
);
