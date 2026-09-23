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

use Novaris\Contracts\Content\{ContentQuery, ContentTypes};
use Novaris\Contracts\Routing\RoutingRouter;
use Novaris\Core\Application;
use Novaris\Export\Exporter;
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
	 * Console arguments.
	 *
	 * @since 1.0.0
	 */
	protected array $arguments = [];

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
			$this->arguments = $arguments;

			$this->app->boot();

			$command = $arguments[1] ?? '';

			return match ( $command ) {
				'export'       => $this->export(),
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
	 * Export the site as static HTML.
	 *
	 * @since 1.0.0
	 */
	protected function export(): int
	{
		$url = $this->option( 'url' );

		$this->output->info(
			'Exporting site...'
		);

		if ( $url ) {
			$this->output->line(
				"  URL: {$url}"
			);
		}

		$exporter = new Exporter(
			$this->app->make( RoutingRouter::class ),
			$this->app->make( ContentTypes::class ),
			$this->app->make( ContentQuery::class ),
			$this->app['path'] . '/dist',
			$url
		);

		$exported = $exporter->export();

		foreach ( $exported as $path ) {
			$this->output->line(
				"  {$path}"
			);
		}

		$this->output->success(
			sprintf(
				'Exported %d static HTML file%s.',
				count( $exported ),
				1 === count( $exported ) ? '' : 's'
			)
		);

		return 0;
	}

	/**
	 * Get a command-line option.
	 *
	 * Supports both --option=value and --option value.
	 *
	 * @since 1.0.0
	 */
	protected function option( string $name ): ?string
	{
		$option = "--{$name}";

		foreach ( $this->arguments as $index => $argument ) {

			if ( str_starts_with( $argument, "{$option}=" ) ) {
				$value = substr(
					$argument,
					strlen( $option ) + 1
				);

				return '' !== $value ? $value : null;
			}

			if ( $option === $argument ) {
				$value = $this->arguments[ $index + 1 ] ?? null;

				if (
					null !== $value
					&& ! str_starts_with( $value, '--' )
				) {
					return $value;
				}

				return null;
			}
		}

		return null;
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
		$this->output->line( '  php novaris <command> [options]' );
		$this->output->line();
		$this->output->line( 'Available commands:' );
		$this->output->line( '  export          Export the site as static HTML' );
		$this->output->line( '  theme:update    Update the active theme or its parent theme' );
		$this->output->line( '  version         Display the Novaris version' );
		$this->output->line( '  help            Display available commands' );
		$this->output->line();
		$this->output->line( 'Export options:' );
		$this->output->line( '  --url=<url>      Set the URL for the exported site' );

		return 0;
	}
}