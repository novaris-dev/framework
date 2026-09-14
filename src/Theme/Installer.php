<?php
/**
 * Theme installer.
 *
 * Handles locating, downloading, and installing theme release packages
 * from GitHub.
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
use ZipArchive;

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

		$version  = ltrim( $release['tag_name'], 'v' );
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

	/**
	 * Download the latest theme release.
	 *
	 * @since 1.0.0
	 */
	public function download(
		string $theme,
		string $repository,
		string $destination
	): string {
		$release = $this->latest( $theme, $repository );

		if ( ! is_dir( $destination ) ) {
			if ( ! mkdir( $destination, 0755, true ) && ! is_dir( $destination ) ) {
				throw new RuntimeException(
					"Unable to create theme download directory: {$destination}"
				);
			}
		}

		$file = rtrim( $destination, '/\\' )
			. DIRECTORY_SEPARATOR
			. $release['filename'];

		$this->client->request(
			'GET',
			$release['url'],
			[
				'sink' => $file,
			]
		);

		if ( ! is_file( $file ) ) {
			throw new RuntimeException(
				"Unable to download theme release: {$release['filename']}"
			);
		}

		return $file;
	}

	/**
	 * Install the latest theme release.
	 *
	 * @since 1.0.0
	 */
	public function install(
		string $theme,
		string $repository,
		string $themes
	): string {
		if ( ! class_exists( ZipArchive::class ) ) {
			throw new RuntimeException(
				'The PHP ZIP extension is required to install themes.'
			);
		}

		if ( ! is_dir( $themes ) ) {
			if ( ! mkdir( $themes, 0755, true ) && ! is_dir( $themes ) ) {
				throw new RuntimeException(
					"Unable to create themes directory: {$themes}"
				);
			}
		}

		$archive = $this->download(
			$theme,
			$repository,
			$themes
		);

		$zip = new ZipArchive();

		if ( $zip->open( $archive ) !== true ) {
			throw new RuntimeException(
				"Unable to open theme archive: {$archive}"
			);
		}

		$extracted = $zip->extractTo( $themes );

		$zip->close();

		if ( ! $extracted ) {
			throw new RuntimeException(
				"Unable to extract theme archive: {$archive}"
			);
		}

		if ( is_file( $archive ) ) {
			unlink( $archive );
		}

		$themePath = rtrim( $themes, '/\\' )
			. DIRECTORY_SEPARATOR
			. $theme;

		if ( ! is_dir( $themePath ) ) {
			throw new RuntimeException(
				"Installed theme directory not found: {$theme}"
			);
		}

		return $themePath;
	}
}