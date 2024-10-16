<?php
/**
 * Template tags registry.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

use Novaris\App;
use Novaris\Contracts\Template\{TemplateTag, TemplateTags};
use Novaris\Tools\Collection;

class Tags extends Collection implements TemplateTags
{
	/**
	 * Creates a new tag object if it exists.
	 *
	 * @since 1.0.0
	 */
	public function callback(
		string $name,
		Collection $data,
		array $args = []
	): ?TemplateTag
	{
		// Check if the tag is registered and that its class exists.
		if ( $this->has( $name ) && class_exists( $this->get( $name ) ) ) {
			$callback = $this->get( $name );

			// Creates a new object from the registered tag class.
			$tag = new $callback( ...$args );

			// Set the data before returning the tag.
			$tag->setData( $data );
			return $tag;
		}

		return null;
	}
}
