<?php
namespace PNODEV\CLI\Command;

use WP_CLI;

/**
 * Manage Posterno through the command-line.
 */
class Posterno extends PNOCommand {

	/**
	 * Adds description and subcomands to the DOC.
	 *
	 * @param  string $command Command.
	 * @return string
	 */
	private function command_to_array( $command ) {
		$dump = array(
			'name'        => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc'    => $command->get_longdesc(),
		);

		foreach ( $command->get_subcommands() as $subcommand ) {
			$dump['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $dump['subcommands'] ) ) {
			$dump['synopsis'] = (string) $command->get_synopsis();
		}

		return $dump;
	}

}
