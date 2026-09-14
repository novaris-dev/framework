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
			$slug = basename(
				rtrim( $theme, '/\\' )
			);

			if ( $slug === 'amicable' ) {
				return [
					'name'       => 'Amicable',
					'slug'       => 'amicable',
					'version'    => '0.0.1',
					'repository' => 'novaris-dev/amicable',
				];
			}

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
}