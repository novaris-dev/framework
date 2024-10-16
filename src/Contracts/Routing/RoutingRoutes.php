<?php
/**
 * Routes registry interface.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Routing;

interface RoutingRoutes
{
	/**
	 * Returns route by name.
	 *
	 * @since  1.0.0
	 */
	public function getNamedRoute( string $name ): ?RoutingRoute;

	/**
	 * Returns an array of all routes with their names as the keys and the
	 * Route objects as the values.
	 *
	 * @since  1.0.0
	 */
	public function getRoutesByName(): array;
}