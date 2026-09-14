<?php
/**
 * Theme service provider.
 *
 * Registers theme-related services with the application container.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Theme;

use Novaris\Core\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
	/**
	 * Register theme services.
	 *
	 * @since 1.0.0
	 */
	public function register(): void
	{
		$this->app->singleton(
			'theme.metadata',
			fn () => new Metadata()
		);

		$this->app->singleton(
			'theme.installer',
			fn () => new Installer(
				$this->app['theme.metadata']
			)
		);
	}
}