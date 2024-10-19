<?php
/**
 * Template tags interface.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Template;

// Concretes.
use Novaris\Tools\Collection;

interface TemplateTags
{
	/**
	 * Creates a new tag object if it exists.
	 *
	 * @since 1.0.0
	 */
	public function callback( string $name, Collection $data, array $args = [] ): ?TemplateTag;
}
