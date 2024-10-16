<?php
/**
 * Cache service provider.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core\Providers;

use Novaris\Contracts\Cache\Registry as RegistryContract;

use Novaris\Core\ServiceProvider;
use Novaris\Cache\{Component, Registry};
use Novaris\Cache\Drivers\{File, JsonFile};

class Cache extends ServiceProvider
{
	/**
	 * Register bindings.
	 *
	 * @since 1.0.0
	 */
        public function register(): void
	{
		// Bind cache registry.
                $this->app->singleton( RegistryContract::class, Registry::class );

		// Binds the cache component.
		$this->app->singleton( Component::class, function( $app ) {

			// Merge default and user-configured drivers.
			$drivers = array_merge( [
				'file'       => File::class,
				'file.cache' => File::class,
				'file.json'  => JsonFile::class
			], $app->make( 'config' )->get( 'cache.drivers' ) );

			// Merge default and user-configured stores.
			$stores = array_merge( [
				'content' => [
					'driver' => 'file.json',
					'path'   => $app->cachePath( 'content' )
				],
				'global'  => [
					'driver' => 'file.cache',
					'path'   => $app->cachePath( 'global' )
				]
			], $app->make( 'config' )->get( 'cache.stores' ) );

			// Creates the cache component.
			return new Component(
				$app->make( RegistryContract::class ),
				$drivers,
				$stores
			);
		} );

		// Add aliases.
		$this->app->alias( RegistryContract::class, 'cache.registry' );
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
