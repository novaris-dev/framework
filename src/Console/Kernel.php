<?php
/**
 * Console kernel.
 *
 * Handles Novaris command-line commands.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Console;

use Novaris\Core\Application;

class Kernel
{
	/**
	 * Application instance.
	 *
	 * @since 1.0.0
	 */
	protected Application $app;

	/**
	 * Create a new console kernel.
	 *
	 * @since 1.0.0
	 */
	public function __construct( Application $app )
	{
		$this->app = $app;
	}

	/**
	 * Run the console command.
	 *
	 * @since 1.0.0
	 */
	public function run( array $arguments ): int
	{
		$this->app->boot();

		$command = $arguments[1] ?? '';

		return match ( $command ) {
			'theme:update' => $this->updateTheme(),
			default        => $this->help(),
		};
	}

	/**
	 * Update the active theme or its parent theme.
	 *
	 * When the active theme is a child theme, the parent theme is updated
	 * instead. Child themes are not updated automatically.
	 *
	 * @since 1.0.0
	 */
	protected function updateTheme(): int
	{
		$installer = $this->app['theme.installer'];
		$metadata  = $this->app['theme.metadata'];

		$themePath = $this->app->parentThemePath()
			?: $this->app->themePath();

		$current = $metadata->read( $themePath );

		$theme   = $current['name'] ?? $current['slug'] ?? 'Theme';
		$version = $current['version'] ?? '';

		if ( ! $installer->updateAvailable( $themePath ) ) {
			echo "Theme: {$theme} is already up to date.\n";

			return 0;
		}

		$installer->update( $themePath );

		$updated = $metadata->read( $themePath );

		$newVersion = $updated['version'] ?? '';

		echo "Theme: {$theme} updated successfully from {$version} to {$newVersion}.\n";

		return 0;
	}

	/**
	 * Display available console commands.
	 *
	 * @since 1.0.0
	 */
	protected function help(): int
	{
		echo "Novaris\n\n";
		echo "Available commands:\n";
		echo "  theme:update    Update the active theme or its parent theme\n";

		return 0;
	}
}