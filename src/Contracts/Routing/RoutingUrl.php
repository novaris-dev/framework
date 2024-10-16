<?php
/**
 * URL interface.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Routing;

interface RoutingUrl
{
	/**
	 * Return the app URL and append an optional path.
	 *
	 * @since 1.0.0
	 */
	public function to( string $append = '' ): string;

	/**
	 * Returns a route's URL.
	 *
	 * @since 1.0.0
	 */
	public function route( string $name, array $params = [] ): string;

	/**
	 * Accepts a path or URL string with possible `{param}` values in it.
	 * Replaces the `{param}` strings with values from the `$params` array.
	 *
	 * @since 1.0.0
	 */
	public function parseParams( string $path, array $params = [] ): string;
}