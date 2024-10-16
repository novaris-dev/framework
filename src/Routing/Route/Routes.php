<?php
/**
 * Routes registry.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Routing\Route;

use Novaris\Core\Proxies\App;
use Novaris\Contracts\Routing\{RoutingRoute, RoutingRoutes};
use Novaris\Tools\Collection;

class Routes extends Collection implements RoutingRoutes
{
	/**
	 * Holds an array of the route objects by name.
	 *
	 * @since 1.0.0
	 */
	protected array $named_routes = [];

	/**
	 * Stores an array of route groups.
	 *
	 * @since 1.0.0
	 */
	protected array $groups = [];

	/**
	 * Add a route.
	 *
	 * @since 1.0.0
	 */
	public function add( mixed $uri, mixed $options = [] ): void
	{
		parent::add( $uri, App::make( 'routing.route', [
			'uri'     => $uri,
			'options' => $options
		] ) );

		$route = $this->get( $uri )->make();

		$this->named_routes[ $route->getName() ] = $route;
	}

	/**
	 * Adds a new route group.
	 *
	 * @since 1.0.0
	 */
	public function addGroup( string $name, array $routes = [] ): void
	{
		$this->groups[ $name ] = [];

		$prefix = trim( $name, '/' );

		foreach ( $routes as $uri => $options ) {
			$uri = trim( $uri, '/' );
			$uri = $uri ? "{$prefix}/{$uri}" : $prefix;

			if ( $uri ) {
				$this->add( $uri, $options );
				$this->groups[ $name ][] = $this->get( $uri );
			}
		}
	}

	/**
	 * Returns the route groups.
	 *
	 * @since 1.0.0
	 */
	public function groups(): array
	{
		return $this->groups;
	}

	/**
	 * Returns route by name.
	 *
	 * @since 1.0.0
	 */
	public function getNamedRoute( string $name ): ?RoutingRoute
	{
		return $this->named_routes[ $name ] ?? null;
	}

	/**
	 * Returns an array of all routes with their names as the keys and the
	 * Route objects as the values.
	 *
	 * @since 1.0.0
	 */
	public function getRoutesByName(): array
	{
		if ( $this->named_routes ) {
			return $this->named_routes;
		}

		foreach ( $this->all() as $route ) {
			$this->named_routes[ $route->getName() ] = $route;
		}

		return $this->named_routes;
	}
}
