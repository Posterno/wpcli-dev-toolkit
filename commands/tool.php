<?php
namespace PNODEV\CLI\Command;

use WP_CLI;

/**
 * Manage tools.
 */
class Tool extends PNOCommand {

	/**
	 * Display Posterno version currently installed.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp pno tool version
	 *     Posterno: 3.0.0
	 */
	public function version() {
		WP_CLI::line( 'Posterno: ' . PNO_VERSION );
	}
}
