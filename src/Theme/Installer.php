<?php
/**
 * Theme installer.
 *
 * Handles locating, downloading, installing, checking, and updating theme
 * release packages from GitHub.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Theme;

use FilesystemIterator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class Installer
{
	/**
	 * Default theme repository organization.
	 *
	 * @since 1.0.0
	 */
	protected const DEFAULT_REPOSITORY = 'novaris-dev';

	/**
	 * GitHub API client.
	 *
	 * @since 1.0.0
	 */
	protected Client $client;

	/**
	 * Theme metadata.
	 *
	 * @since 1.0.0
	 */
	protected Metadata $metadata;

	/**
	 * Create a new theme installer.
	 *
	 * @since 1.0.0
	 */
	public function __construct( Metadata $metadata )
	{
		$this->metadata = $metadata;

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
	 * Get the latest theme release.
	 *
	 * @since 1.0.0
	 */
	public function latest( string $theme, string $repository ): array
	{
		try {
			$response = $this->client->request(
				'GET',
				"/repos/{$repository}/releases/latest"
			);
		} catch ( ClientException $e ) {
			if ( 404 === $e->getResponse()->getStatusCode() ) {
				throw new RuntimeException(
					"Unable to find a release for theme: {$theme}"
				);
			}

			throw $e;
		}

		$release = json_decode(
			$response->getBody()->getContents(),
			true
		);

		if ( empty( $release['tag_name'] ) ) {
			throw new RuntimeException(
				"Unable to find a release for theme: {$theme}"
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

		$themePath = rtrim( $themes, '/\\' )
			. DIRECTORY_SEPARATOR
			. $theme;

		if ( is_dir( $themePath ) ) {
			return $themePath;
		}

		$repository = static::DEFAULT_REPOSITORY . "/{$theme}";

		$temporary = rtrim( $themes, '/\\' )
			. DIRECTORY_SEPARATOR
			. ".{$theme}-install-" . uniqid();

		if ( ! mkdir( $temporary, 0755, true ) && ! is_dir( $temporary ) ) {
			throw new RuntimeException(
				"Unable to create temporary theme directory: {$temporary}"
			);
		}

		$archive = '';

		try {
			$archive = $this->download(
				$theme,
				$repository,
				$temporary
			);

			$zip = new ZipArchive();

			if ( $zip->open( $archive ) !== true ) {
				throw new RuntimeException(
					"Unable to open theme archive: {$archive}"
				);
			}

			$extracted = $zip->extractTo( $temporary );

			$zip->close();

			if ( ! $extracted ) {
				throw new RuntimeException(
					"Unable to extract theme archive: {$archive}"
				);
			}

			$source = $this->locateTheme( $temporary, $theme );

			if ( ! rename( $source, $themePath ) ) {
				throw new RuntimeException(
					"Unable to install theme: {$theme}"
				);
			}

			return $themePath;
		} finally {
			if ( $archive && is_file( $archive ) ) {
				unlink( $archive );
			}

			if ( is_dir( $temporary ) ) {
				$this->removeDirectory( $temporary );
			}
		}
	}

	/**
	 * Determine whether a theme update is available.
	 *
	 * @since 1.0.0
	 */
	public function updateAvailable( string $themePath ): bool
	{
		$metadata = $this->metadata->read( $themePath );

		$theme      = $metadata['slug'] ?? '';
		$version    = $metadata['version'] ?? '';
		$repository = $metadata['repository'] ?? '';

		if ( empty( $theme ) || empty( $version ) || empty( $repository ) ) {
			throw new RuntimeException(
				"Theme metadata is incomplete: {$themePath}"
			);
		}

		$latest = $this->latest(
			$theme,
			$repository
		);

		return version_compare(
			$latest['version'],
			$version,
			'>'
		);
	}

	/**
	 * Update an installed theme to the latest release.
	 *
	 * @since 1.0.0
	 */
	public function update( string $themePath ): string
	{
		if ( ! class_exists( ZipArchive::class ) ) {
			throw new RuntimeException(
				'The PHP ZIP extension is required to update themes.'
			);
		}

		$metadata = $this->metadata->read( $themePath );

		$theme      = $metadata['slug'] ?? '';
		$repository = $metadata['repository'] ?? '';

		if ( empty( $theme ) || empty( $repository ) ) {
			throw new RuntimeException(
				"Theme metadata is incomplete: {$themePath}"
			);
		}

		if ( ! $this->updateAvailable( $themePath ) ) {
			return $themePath;
		}

		$themes = dirname( $themePath );

		$temporary = $themes
			. DIRECTORY_SEPARATOR
			. ".{$theme}-update-" . uniqid();

		$backup = $themes
			. DIRECTORY_SEPARATOR
			. ".{$theme}-backup-" . uniqid();

		if ( ! mkdir( $temporary, 0755, true ) && ! is_dir( $temporary ) ) {
			throw new RuntimeException(
				"Unable to create temporary theme directory: {$temporary}"
			);
		}

		$archive = '';

		try {
			$archive = $this->download(
				$theme,
				$repository,
				$temporary
			);

			$zip = new ZipArchive();

			if ( $zip->open( $archive ) !== true ) {
				throw new RuntimeException(
					"Unable to open theme archive: {$archive}"
				);
			}

			$extracted = $zip->extractTo( $temporary );

			$zip->close();

			if ( ! $extracted ) {
				throw new RuntimeException(
					"Unable to extract theme archive: {$archive}"
				);
			}

			$updatedThemePath = $this->locateTheme(
				$temporary,
				$theme
			);

			if ( ! rename( $themePath, $backup ) ) {
				throw new RuntimeException(
					"Unable to create backup for theme: {$theme}"
				);
			}

			if ( ! rename( $updatedThemePath, $themePath ) ) {
				rename( $backup, $themePath );

				throw new RuntimeException(
					"Unable to replace installed theme: {$theme}"
				);
			}

			$this->removeDirectory( $backup );

			return $themePath;
		} finally {
			if ( $archive && is_file( $archive ) ) {
				unlink( $archive );
			}

			if ( is_dir( $temporary ) ) {
				$this->removeDirectory( $temporary );
			}
		}
	}

	/**
	 * Locate and validate a theme in an extracted release.
	 *
	 * @since 1.0.0
	 */
	protected function locateTheme(
		string $directory,
		string $theme
	): string {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$directory,
				FilesystemIterator::SKIP_DOTS
			)
		);

		foreach ( $iterator as $item ) {
			if ( ! $item->isFile() || $item->getFilename() !== 'theme.json' ) {
				continue;
			}

			$themePath = $item->getPath();

			$metadata = $this->metadata->read( $themePath );

			if ( ( $metadata['slug'] ?? '' ) === $theme ) {
				return $themePath;
			}
		}

		throw new RuntimeException(
			"Unable to locate theme in release: {$theme}"
		);
	}

	/**
	 * Remove a directory and all of its contents.
	 *
	 * @since 1.0.0
	 */
	protected function removeDirectory( string $directory ): void
	{
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$directory,
				FilesystemIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				rmdir( $item->getPathname() );
			} else {
				unlink( $item->getPathname() );
			}
		}

		rmdir( $directory );
	}
}