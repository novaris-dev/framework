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
	$fonts = app( 'fonts' );

	foreach ( $fonts as $font ) {
		if ( ! $font->isGoogle() ) {
			continue;
		}

		// Rendering will be added next.
	}
}