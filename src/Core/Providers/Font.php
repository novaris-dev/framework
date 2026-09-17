<?php
/**
 * Font service provider.
 *
 * Registers and bootstraps the font services.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\Core\Providers;

use Novaris\Core\ServiceProvider;
use Novaris\Font\{
	Component,
	Fonts
};

/**
 * Font service provider class.
 *
 * @since 1.0.0
 */
class Font extends ServiceProvider
{
	/**
	 * Registers the font services.
	 *
	 * @since 1.0.0
	 */
	public function register(): void
	{
		$this->app->singleton( Fonts::class );

		$this->app->singleton( Component::class, function( $app ) {
			return new Component(
				$app->make( Fonts::class ),
				$app->make( 'config' )->get( 'fonts', [] )
			);
		} );

		$this->app->alias( Fonts::class, 'fonts' );
	}

	/**
	 * Bootstraps the font services.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void
	{
		$this->app->make( Component::class )->boot();
	}
}