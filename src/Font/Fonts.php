<?php
/**
 * Fonts collection.
 *
 * Houses the collection of fonts in a single array-object.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\Font;

use Novaris\Tools\Collection;

/**
 * Fonts class.
 *
 * @since 1.0.0
 */
class Fonts extends Collection
{
	/**
	 * Adds a font to the collection.
	 *
	 * @since 1.0.0
	 */
	public function add( $id, $value ): void
	{
		parent::add(
			$id,
			$value instanceof Font
				? $value
				: new Font( $id, $value )
		);
	}
}