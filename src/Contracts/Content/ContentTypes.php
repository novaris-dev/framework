<?php
/**
 * Content types interface.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Content;

interface ContentTypes
{
	/**
	 * Gets a custom content type by its path.
	 *
	 * @since  1.0.0
	 */
	public function getTypeFromPath( string $path ): ContentType|false;

	/**
	 * Gets a custom content type by its URI.
	 *
	 * @since  1.0.0
	 */
	public function getTypeFromUri( string $uri ): ContentType|false;

	/**
	 * Sorts types by their path.
	 *
	 * @since 1.0.0
	 */
	public function sortByPath(): array;
}