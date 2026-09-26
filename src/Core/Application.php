<?php
/**
 * Application class.
 *
 * This class is essentially a wrapper around the `Container` class that's
 * specific to the framework. This class is meant to be used as the single,
 * one-true instance of the framework.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core;

use Novaris\Contracts\Core\Application as ApplicationContract;
use Novaris\Contracts\Bootable;
use Novaris\Core\{Proxies, Schemas};
use Novaris\Messenger\Message;
use Novaris\Theme\ThemeServiceProvider;
use Novaris\Tools\Str;
use Dotenv\Dotenv;
use League\Config\Configuration;
use Novaris\Template\Tag\Navigation;
use Throwable;

/**
 * Application class.
 *
 * @since  1.0.0
 * @access public
 */
class Application extends Container implements ApplicationContract, Bootable
{
	/**
	 * The current version of the framework.
	 *
	 * @since 1.0.0
	 */
	const VERSION = '1.0.0';

	/**
	 * Registers the default bindings, providers, and proxies for the
	 * framework.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $path )
	{
		$this->instance( 'path', Str::normalizePath( $path ) );

		$this->registerDefaultConstants();
		$this->registerDefaultBindings();
		$this->registerDefaultProviders();
		$this->registerDefaultProxies();
	}

	/**
	 * Calls the functions to register and boot providers and proxies.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void
	{
		$this->registerProviders();
		$this->registerProxies();
		$this->loadTheme();
		$this->bootProviders();
	}

	/**
	 * Loads the active theme bootstrap file.
	 *
	 * @since 1.0.0
	 */
	protected function loadTheme(): void
	{
		if ( $this['config']->get( 'app.private' ) ) {
			return;
		}

		$theme = $this['config']->get( 'app.theme', '' );

		if ( empty( $theme ) ) {
			( new Message() )->make(
				'No active theme has been configured.'
			)->dd();
		}

		try {
			if ( ! is_dir( $this->themePath() ) ) {
				$this['theme.installer']->install(
					$theme,
					$this['path.themes']
				);
			}

			$parent = $this['theme.metadata']->parent();

			if ( $parent && ! is_dir( $this->themesPath( $parent ) ) ) {
				$this['theme.installer']->install(
					$parent,
					$this['path.themes']
				);
			}
		} catch ( Throwable $e ) {
			( new Message() )->make(
				$e->getMessage()
			)->dd();
		}
	}

	/**
	 * Registers the default constants provided by the framework.
	 *
	 * @since 1.0.0
	 */
	protected function registerDefaultConstants(): void
	{
		if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
			define( 'MINUTE_IN_SECONDS', 60 );
		}

		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
		}

		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
		}

		if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
			define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
		}

		if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
			define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
		}

		if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
			define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );
		}
	}

	/**
	 * Registers the default bindings we need to run the framework.
	 *
	 * @since 1.0.0
	 */
	protected function registerDefaultBindings(): void
	{
		// Add the instance of this application.
		$this->instance( 'app', $this );

		// Add the version for the framework.
		$this->instance( 'version', static::VERSION );

		// Check whether an environment file exists.
		$has_env_file =
			file_exists( Str::appendPath( $this['path'], '.env' ) ) ||
			file_exists( Str::appendPath( $this['path'], '.env.local' ) );

		// Environment variables may also be provided directly by the
		// server or container environment.
		$has_environment =
			isset( $_ENV['APP_URL'] ) ||
			getenv( 'APP_URL' ) !== false;

		// Require either an environment file or server environment.
		if ( ! $has_env_file && ! $has_environment ) {
			( new Message() )->make(
				'No .env or .env.local file found for the application. If setting up Novaris for the first time, copy and rename the .env.example file.'
			)->dd();
		}

		// Load the dotenv file when available and parse its data, making
		// it available through the `$_ENV` and `$_SERVER` super-globals.
		if ( $has_env_file ) {
			Dotenv::createImmutable(
				$this->path,
				[ '.env.local', '.env' ]
			)->load();
		}

		// Creates a new configuration instance and adds the default
		// framework schemas.
		$this->instance( Configuration::class, new Configuration( [
			'app'      => Schemas\App::schema(),
			'cache'    => Schemas\Cache::schema(),
			'content'  => Schemas\Content::schema(),
			'fonts'    => Schemas\Fonts::schema(),
			'markdown' => Schemas\Markdown::schema(),
			'template' => Schemas\Template::schema()
		] ) );

		// Add alias for configuration.
		$this->alias( Configuration::class, 'config' );

		// Add config path early (cannot change).
		$this->instance( 'path.config', Str::appendPath( $this['path'], 'config' ) );

		// Loop through user-supplied config files and set the data.
		foreach ( [ 'app', 'cache', 'content', 'fonts', 'markdown', 'template' ] as $type ) {
			$filepath = Str::appendPath( $this['path.config'], "{$type}.php" );

			if ( file_exists( $filepath ) ) {
				$this['config']->set( $type, include $filepath );
			}
		}

		$theme = $this['config']->get( 'app.theme', '' );

		// Load the active theme app configuration as defaults and allow the
		// application app configuration to override those values.
		if ( ! $this['config']->get( 'app.private', false ) && $theme ) {
			$themeConfig = Str::appendPath(
				$this['path'],
				"themes/{$theme}/config/app.php"
			);

			$appConfig = Str::appendPath(
				$this['path.config'],
				'app.php'
			);

			if ( file_exists( $themeConfig ) ) {
				$defaults  = include $themeConfig;
				$overrides = file_exists( $appConfig )
					? include $appConfig
					: [];

				$this['config']->set(
					'app',
					array_replace_recursive(
						$defaults,
						$overrides
					)
				);
			}
		}

		// Add default paths.
		$this->instance( 'path.app',      $this['path']                                         );
		$this->instance( 'path.public',   Str::appendPath( $this['path'],         'public'    ) );
		$this->instance( 'path.view',     Str::appendPath( $this['path.public'],  'views'     ) );
		$this->instance( 'path.resource', Str::appendPath( $this['path'],         'resources' ) );
		$this->instance( 'path.storage',  Str::appendPath( $this['path'],         'storage'   ) );
		$this->instance( 'path.cache',    Str::appendPath( $this['path.storage'], 'cache'     ) );
		$this->instance( 'path.user',     Str::appendPath( $this['path'],         'user'      ) );
		$this->instance( 'path.content',  Str::appendPath( $this['path.user'],    'content'   ) );
		$this->instance( 'path.media',    Str::appendPath( $this['path.user'],    'media'     ) );
		$this->instance( 'path.vendor',   Str::appendPath( $this['path'],         'vendor'    ) );
		$this->instance( 'path.themes',   Str::appendPath( $this['path'],         'themes'    ) );
		$this->instance(
			'path.theme',
			$this['config']->get( 'app.private', false )
				? $this['path']
				: Str::appendPath(
					$this['path.themes'],
					$theme
				)
		);

		// Add default URIs.
		if ( ! $url = $this->config->get( 'app.uri' ) ) {
			$url = $this->config->get( 'app.url' );
		}

		$this->instance( 'url',          $url                                                 );
		$this->instance( 'url.app',      $this['url']                                         );
		$this->instance( 'url.config',   Str::appendPath( $this['url'],         'config'    ) );
		$this->instance( 'url.public',   Str::appendPath( $this['url'],         'public'    ) );
		$this->instance( 'url.view',     Str::appendPath( $this['url.public'],  'views'     ) );
		$this->instance( 'url.resource', Str::appendPath( $this['url'],         'resources' ) );
		$this->instance( 'url.storage',  Str::appendPath( $this['url'],         'storage'   ) );
		$this->instance( 'url.cache',    Str::appendPath( $this['url.storage'], 'cache'     ) );
		$this->instance( 'url.user',     Str::appendPath( $this['url'],         'user'      ) );
		$this->instance( 'url.content',  Str::appendPath( $this['url.user'],    'content'   ) );
		$this->instance( 'url.media',    Str::appendPath( $this['url.user'],    'media'     ) );
		$this->instance( 'url.vendor',   Str::appendPath( $this['url'],         'vendor'    ) );

		// Register Navigation as a singleton with the default items
		$this->singleton( 'navigation', function () {
			$items = config( 'app.primary' ) ?? [];

			return new Navigation( $items );
		});
	}

	/**
	 * Registers the default service providers.
	 *
	 * @since 1.0.0
	 */
	protected function registerDefaultProviders(): void
	{
		// Register framework service providers.
		$this->provider( Providers\App::class      );
		$this->provider( Providers\Cache::class    );
		$this->provider( Providers\Content::class  );
		$this->provider( Providers\Font::class     );
		$this->provider( Providers\Markdown::class );
		$this->provider( Providers\Routing::class  );
		$this->provider( Providers\Template::class );
		$this->provider( ThemeServiceProvider::class );

		// Register app service providers.
		$providers = $this['config']->get( 'app.providers' );

		foreach ( $providers as $provider ) {
			$this->provider( $provider );
		}
	}

	/**
	 * Adds the default static proxy classes.
	 *
	 * @since 1.0.0
	 */
	protected function registerDefaultProxies(): void
	{
		Proxy::setContainer( $this );

		// Register framework proxies.
		$this->proxy( Proxies\App::class,       '\Novaris\App'       );
		$this->proxy( Proxies\Cache::class,     '\Novaris\Cache'     );
		$this->proxy( Proxies\Config::class,    '\Novaris\Config'    );
		$this->proxy( Proxies\Engine::class,    '\Novaris\Engine'    );
		$this->proxy( Proxies\Message::class,   '\Novaris\Message'   );
		$this->proxy( Proxies\PoweredBy::class, '\Novaris\PoweredBy' );
		$this->proxy( Proxies\Query::class,     '\Novaris\Query'     );
		$this->proxy( Proxies\Url::class,       '\Novaris\Url'       );

		// Register app proxies.
		$proxies = $this['config']->get( 'app.proxies' );

		foreach ( $proxies as $abstract => $proxy ) {
			$this->proxy( $abstract, $proxy );
		}
	}

	/**
	 * Access a keyed path and append a path to it.
	 *
	 * @since  1.0.0
	 */
	public function path( string $accessor = '', string $append = '' ): string
	{
		$path = $accessor ? $this->get( "path.{$accessor}" ) : $this->path;

		return Str::appendPath( $path, $append );
	}

	/**
	 * Returns app path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function appPath( string $append = '' ): string
	{
		return $this->path( 'app', $append );
	}

	/**
	 * Returns config path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function configPath( string $append = '' ): string
	{
		return $this->path( 'config', $append );
	}

	/**
	 * Returns public path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function publicPath( string $append = '' ): string
	{
		return $this->path( 'public', $append );
	}

	/**
	 * Returns view path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function viewPath( string $append = '' ): string
	{
		return $this->path( 'view', $append );
	}

	/**
	 * Returns resource path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function resourcePath( string $append = '' ): string
	{
		return $this->path( 'resource', $append );
	}

	/**
	 * Returns storage path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function storagePath( string $append = '' ): string
	{
		return $this->path( 'storage', $append );
	}

	/**
	 * Returns cache path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function cachePath( string $append = '' ): string
	{
		return $this->path( 'cache', $append );
	}

	/**
	 * Returns user path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function userPath( string $append = '' ): string
	{
		return $this->path( 'user', $append );
	}

	/**
	 * Returns content path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function contentPath( string $append = '' ): string
	{
		return $this->path( 'content', $append );
	}

	/**
	 * Returns media path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function mediaPath( string $append = '' ): string
	{
		return $this->path( 'media', $append );
	}

	/**
	 * Returns vendor path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function vendorPath( string $append = '' ): string
	{
		return $this->path( 'vendor', $append );
	}

	/**
	 * Returns themes path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function themesPath( string $append = '' ): string
	{
		return $this->path( 'themes', $append );
	}

	/**
	 * Returns active theme path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function themePath( string $append = '' ): string
	{
		return $this->path( 'theme', $append );
	}

	/**
	 * Returns parent theme path with optional appended path/file.
	 *
	 * Returns an empty string if the active theme does not have a parent.
	 *
	 * @since 1.0.0
	 */
	public function parentThemePath( string $append = '' ): string
	{
		$parent = $this['theme.metadata']->parent();

		if ( ! $parent ) {
			return '';
		}

		return $this->themesPath(
			Str::appendPath( $parent, $append )
		);
	}

	/**
	 * Access a keyed URL and append a path to it.
	 *
	 * @since  1.0.0
	 */
	public function url( string $accessor = '', string $append = '' ): string
	{
		$url = $accessor ? $this->get( "url.{$accessor}" ) : $this->url;

		return Str::appendPath( $url, $append );
	}

	/**
	 * Returns app URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function appUrl( string $append = '' ): string
	{
		return $this->url( 'app', $append );
	}

	/**
	 * Returns config URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function configUrl( string $append = '' ): string
	{
		return $this->url( 'config', $append );
	}

	/**
	 * Returns public URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function publicUrl( string $append = '' ): string
	{
		return $this->url( 'public', $append );
	}

	/**
	 * Returns view URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function viewUrl( string $append = '' ): string
	{
		return $this->url( 'view', $append );
	}

	/**
	 * Returns resource URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function resourceUrl( string $append = '' ): string
	{
		return $this->url( 'resource', $append );
	}

	/**
	 * Returns storage URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function storageUrl( string $append = '' ): string
	{
		return $this->url( 'storage', $append );
	}

	/**
	 * Returns cache URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function cacheUrl( string $append = '' ): string
	{
		return $this->url( 'cache', $append );
	}

	/**
	 * Returns user URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function userUrl( string $append = '' ): string
	{
		return $this->url( 'user', $append );
	}

	/**
	 * Returns content URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function contentUrl( string $append = '' ): string
	{
		return $this->url( 'content', $append );
	}

	/**
	 * Returns media URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function mediaUrl( string $append = '' ): string
	{
		return $this->url( 'media', $append );
	}

	/**
	 * Returns vendor URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	public function vendorUrl( string $append = '' ): string
	{
		return $this->url( 'vendor', $append );
	}
}