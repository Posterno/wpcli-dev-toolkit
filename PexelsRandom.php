<?php

namespace PNODEV\CLI\Command;

use Glooby\Pexels\Client;

/**
 * Custom Pexels API Client to randomly retrieve images.
 */
class PexelsRandom extends Client {

	/**
	 * @var string
	 */
	private $token;

	/**
	 * @var \GuzzleHttp\Client
	 */
	private $client;

	public function __construct( $token ) {
		$this->token = $token;
	}

	private function getNewClient() {
		if ( null === $this->client ) {
			$this->client = new \GuzzleHttp\Client(
				[
					'base_uri' => 'https://api.pexels.com/v1/',
					'headers'  => [
						'Authorization' => $this->token,
					],
				]
			);
		}
		return $this->client;
	}

	/**
	 * Retrieve a random image from pexels.
	 *
	 * @return \GuzzleHttp\Message\ResponseInterface
	 */
	public function random() {
		return $this->getNewClient()->get(
			'curated?' . http_build_query(
				[
					'per_page' => 1,
					'page'     => 1,
				]
			)
		);
	}

}
