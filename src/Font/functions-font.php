<?php
/**
 * Font functions.
 *
 * Provides helper functions for working with theme fonts.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\Theme\Font;

/**
 * Displays the configured Google Fonts.
 *
 * @since 1.0.0
 */
function fonts(): void
{
	$families = [];

	foreach ( app( 'fonts' ) as $font ) {
		if ( ! $font->isGoogle() ) {
			continue;
		}

		$family = 'family=' . $font->google();

		if ( $font->styles() ) {
			$family .= ':wght@' . implode(
				';',
				array_filter(
					$font->styles(),
					fn( $style ) => ! str_ends_with( $style, 'i' )
				)
			);
		}

		$families[] = $family;
	}

	if ( ! $families ) {
		return;
	}

	$url = 'https://fonts.googleapis.com/css2?' .
		implode( '&', $families ) .
		'&display=swap';

	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . PHP_EOL;
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . PHP_EOL;
	echo '<link rel="stylesheet" href="' . e( $url ) . '">' . PHP_EOL;
}