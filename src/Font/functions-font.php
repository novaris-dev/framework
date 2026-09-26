<?php
/**
 * Font functions.
 *
 * Provides helper functions for working with fonts.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\Font;

/**
 * Displays the configured Google Fonts.
 *
 * @since 1.0.0
 */
function fonts(): void
{
	$families = [];

	foreach ( app( 'fonts' ) as $font ) {
		if ( ! $font instanceof Font || ! $font->isGoogle() ) {
			continue;
		}

		$normal = [];
		$italic = [];

		foreach ( $font->styles() as $style ) {
			if ( str_ends_with( $style, 'i' ) ) {
				$italic[] = rtrim( $style, 'i' );
			} else {
				$normal[] = $style;
			}
		}

		$family = 'family=' . $font->google();

		if ( $normal && $italic ) {
			$styles = [];

			foreach ( $normal as $weight ) {
				$styles[] = "0,{$weight}";
			}

			foreach ( $italic as $weight ) {
				$styles[] = "1,{$weight}";
			}

			$family .= ':ital,wght@' . implode( ';', $styles );
		} elseif ( $italic ) {
			$styles = [];

			foreach ( $italic as $weight ) {
				$styles[] = "1,{$weight}";
			}

			$family .= ':ital,wght@' . implode( ';', $styles );
		} elseif ( $normal ) {
			$family .= ':wght@' . implode( ';', $normal );
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