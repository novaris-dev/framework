<?php
/**
 * Theme metadata.
 *
 * Handles reading theme metadata from theme.json.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Theme;

use RuntimeException;

class Metadata
{
	/**
	 * Read theme metadata.
	 *
	 * @since 1.0.0
	 */
	public function read( string $theme ): array
	{
		$file = rtrim( $theme, '/\\' )
			. DIRECTORY_SEPARATOR
			. 'theme.json';

		if ( ! is_file( $file ) ) {
			throw new RuntimeException(
				"Theme metadata file not found: {$file}"
			);
		}

		$data = json_decode(
			file_get_contents( $file ),
			true
		);

		if ( ! is_array( $data ) ) {
			throw new RuntimeException(
				"Invalid theme metadata file: {$file}"
			);
		}

		return $data;
	}

	/**
	 * Determine whether the active theme supports a feature.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $feature Theme feature.
	 * @return bool            True if the feature is supported.
	 */
	public function supports( string $feature ): bool
	{
		$data = $this->read( theme_path() );

		if ( ! isset( $data['supports'][ $feature ] ) ) {
			return false;
		}

		return true === $data['supports'][ $feature ]
			|| is_array( $data['supports'][ $feature ] );
	}

	/**
	 * Get the active theme's options for a supported feature.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $feature Theme feature.
	 * @return array           Feature options.
	 */
	public function support( string $feature ): array
	{
		$data = $this->read( theme_path() );

		if ( ! isset( $data['supports'][ $feature ] ) ) {
			return [];
		}

		return is_array( $data['supports'][ $feature ] )
			? $data['supports'][ $feature ]
			: [];
	}
}