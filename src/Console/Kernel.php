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
	 * Update the active theme.
	 *
	 * @since 1.0.0
	 */
	protected function updateTheme(): int
	{
		$this->app['theme.installer']->update(
			$this->app->themePath()
		);

		echo "Theme updated successfully.\n";

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
		echo "  theme:update    Update the active theme\n";

		return 0;
	}
}