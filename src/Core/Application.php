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
use Novaris\Tools\{Collection, Config, Str};
use Dotenv\Dotenv;
use League\Config\Configuration;

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
		$this->bootProviders();
	}

	/**
	 * Registers the default constants provided by the framework.
	 *
	 * @since 1.0.0
	 */
	protected function registerDefaultConstants(): void
	{
		define( 'MINUTE_IN_SECONDS',  60                     );
		define( 'HOUR_IN_SECONDS',    60 * MINUTE_IN_SECONDS );
		define( 'DAY_IN_SECONDS',     24 * HOUR_IN_SECONDS   );
		define( 'WEEK_IN_SECONDS',     7 * DAY_IN_SECONDS    );
		define( 'MONTH_IN_SECONDS',   30 * DAY_IN_SECONDS    );
		define( 'YEAR_IN_SECONDS',   365 * DAY_IN_SECONDS    );
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

		// Require the `.env` or `.env.local` file before proceeding.
		if (
			! file_exists( Str::appendPath( $this['path'], '.env' ) ) &&
			! file_exists( Str::appendPath( $this['path'], '.env.local' ) )
		) {
			( new Message() )->make(
				'No <code>.env</code> or <code>.env.local</code> file found for the application. If setting up Novaris for the first time, copy and rename the <code>.env.example</code> file.'
			)->dd();
		}

		// Load the dotenv file and parse its data, making it available
		// through the `$_ENV` and `$_SERVER` super-globals.
		Dotenv::createImmutable( $this->path, [ '.env.local', '.env' ] )->load();

		// Creates a new configuration instance and adds the default
		// framework schemas.
		$this->instance( Configuration::class, new Configuration( [
			'app'      => Schemas\App::schema(),
			'cache'    => Schemas\Cache::schema(),
			'content'  => Schemas\Content::schema(),
			'markdown' => Schemas\Markdown::schema(),
			'template' => Schemas\Template::schema()
		] ) );

		// Add alias for configuration.
		$this->alias( Configuration::class, 'config' );

		// Add config path early (cannot change).
		$this->instance( 'path.config', Str::appendPath( $this['path'], 'config' ) );

		// Loop through user-supplied config files and set the data.
		foreach ( [ 'app', 'cache', 'content', 'markdown', 'template' ] as $type ) {
			$filepath = Str::appendPath( $this['path.config'], "{$type}.php" );

			if ( file_exists( $filepath ) ) {
				$this['config']->set( $type, include $filepath );
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
		$this->provider( Providers\Markdown::class );
		$this->provider( Providers\Routing::class  );
		$this->provider( Providers\Template::class );

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
