<?php
/**
 * Theme installer.
 *
 * Handles locating theme release packages from GitHub.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Theme;

use GuzzleHttp\Client;
use RuntimeException;

class Installer
{
	/**
	 * GitHub API client.
	 *
	 * @since 1.0.0
	 */
	protected Client $client;

	/**
	 * Create a new theme installer.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->client = new Client( [
			'base_uri' => 'https://api.github.com',
			'timeout'  => 10.0,
			'headers'  => [
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'Novaris',
			],
		] );
	}

	/**
	 * Get the latest release asset for a theme.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public function latest( string $theme, string $repository ): array
	{
		$response = $this->client->request(
			'GET',
			"/repos/{$repository}/releases/latest"
		);

		$release = json_decode(
			$response->getBody()->getContents(),
			true
		);

		if ( empty( $release['tag_name'] ) ) {
			throw new RuntimeException(
				"Unable to determine the latest release for theme: {$theme}"
			);
		}

		$version = ltrim( $release['tag_name'], 'v' );
		$filename = "{$theme}.{$version}.zip";

		foreach ( $release['assets'] ?? [] as $asset ) {
			if ( ( $asset['name'] ?? '' ) !== $filename ) {
				continue;
			}

			return [
				'name'       => $theme,
				'version'    => $version,
				'filename'   => $filename,
				'repository' => $repository,
				'url'        => $asset['browser_download_url'],
			];
		}

		throw new RuntimeException(
			"Theme release asset not found: {$filename}"
		);
	}
}