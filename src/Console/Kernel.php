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
use Throwable;

class Kernel
{
	/**
	 * Application instance.
	 *
	 * @since 1.0.0
	 */
	protected Application $app;

	/**
	 * Console output.
	 *
	 * @since 1.0.0
	 */
	protected Output $output;

	/**
	 * Create a new console kernel.
	 *
	 * @since 1.0.0
	 */
	public function __construct( Application $app )
	{
		$this->app    = $app;
		$this->output = new Output();
	}

	/**
	 * Run the console command.
	 *
	 * @since 1.0.0
	 */
	public function run( array $arguments ): int
	{
		try {
			$this->app->boot();

			$command = $arguments[1] ?? '';

			return match ( $command ) {
				'theme:update' => $this->updateTheme(),
				'version'      => $this->version(),
				'help'         => $this->help(),
				default        => $this->help(),
			};
		} catch ( Throwable $e ) {
			$this->output->error( $e->getMessage() );

			return 1;
		}
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

		$activePath = $this->app->themePath();
		$parentPath = $this->app->parentThemePath();
		$themePath  = $parentPath ?: $activePath;

		$active = $metadata->read( $activePath );
		$current = $metadata->read( $themePath );

		$activeTheme = $active['name']
			?? $active['slug']
			?? 'Theme';

		$theme = $current['name']
			?? $current['slug']
			?? 'Theme';

		$version = $current['version'] ?? '';

		if ( $parentPath ) {
			$this->output->info(
				"Active theme: {$activeTheme}"
			);

			$this->output->info(
				"Parent theme: {$theme}"
			);

			$this->output->line();
		}

		$this->output->info(
			"Checking theme '{$theme}'..."
		);

		if ( ! $installer->updateAvailable( $themePath ) ) {
			$this->output->success(
				"{$theme} is already up to date ({$version})."
			);

			return 0;
		}

		$this->output->info(
			"Updating theme '{$theme}'..."
		);

		$installer->update( $themePath );

		$updated = $metadata->read( $themePath );

		$newVersion = $updated['version'] ?? '';

		$this->output->success(
			"Updated {$theme} from {$version} to {$newVersion}."
		);

		return 0;
	}

	/**
	 * Display the Novaris version.
	 *
	 * @since 1.0.0
	 */
	protected function version(): int
	{
		$this->output->line(
			'Novaris ' . Application::VERSION
		);

		return 0;
	}

	/**
	 * Display available console commands.
	 *
	 * @since 1.0.0
	 */
	protected function help(): int
	{
		$this->output->line( 'Novaris CLI' );
		$this->output->line();
		$this->output->line( 'Usage:' );
		$this->output->line( '  php novaris <command>' );
		$this->output->line();
		$this->output->line( 'Available commands:' );
		$this->output->line( '  theme:update    Update the active theme or its parent theme' );
		$this->output->line( '  version         Display the Novaris version' );
		$this->output->line( '  help            Display available commands' );

		return 0;
	}
}