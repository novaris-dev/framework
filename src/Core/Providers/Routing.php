<?php
/**
 * Route service provider.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core\Providers;

use Novaris\Contracts\Routing\{
	RoutingRoute,
	RoutingRouter,
	RoutingRoutes,
	RoutingUrl
};

use Novaris\Core\ServiceProvider;
use Novaris\Routing\{Component, Router, Url};
use Novaris\Routing\Route\{Route, Routes};

class Routing extends ServiceProvider
{
	/**
	 * Register bindings.
	 *
	 * @since 1.0.0
	 */
        public function register(): void
	{
		// Bind route.
		$this->app->bind( RoutingRoute::class, Route::class );

		// Bind routes.
		$this->app->singleton( RoutingRoutes::class, Routes::class );

		// Binds the router.
                $this->app->singleton( RoutingRouter::class, function( $app ) {
			return new Router( $app->make( RoutingRoutes::class ) );
		} );

		// Binds the routing URL instance.
                $this->app->singleton( RoutingUrl::class, function( $app ) {
			return new Url( $app->make( RoutingRoutes::class ) );
		} );

		// Binds the routing component.
		$this->app->singleton( Component::class, function( $app ) {
			return new Component(
				$app->make( RoutingRoutes::class ),
				$app->make( 'content.types' )
			);
		} );

		// Add aliases.
		$this->app->alias( RoutingRoute::class,  'routing.route'  );
		$this->app->alias( RoutingRoutes::class, 'routing.routes' );
		$this->app->alias( RoutingRouter::class, 'routing.router' );
		$this->app->alias( RoutingUrl::class,    'routing.url'    );
        }

	/**
	 * Bootstrap bindings.
	 *
	 * @since 1.0.0
	 */
        public function boot(): void
	{
                $this->app->make( Component::class )->boot();
        }
}
