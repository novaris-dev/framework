<?php
/**
 * Helper functions.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

use Novaris\Core\Proxies\{App, Config, Url};
use Novaris\Tools\{Collection, Str};

if ( ! function_exists( 'app' ) ) {
	/**
	 * Returns an instance of the application or a binding.
	 *
	 * @since  1.0.0
	 */
	function app( string $abstract = '', array $params = [] ): mixed
	{
		return App::resolve( $abstract ?: 'app', $params );
	}
}

if ( ! function_exists( 'env' ) ) {
	/**
	 * Returns an environment variable if it is set or `null`.
	 *
	 * @since  1.0.0
	 */
	function env( string $var, ?string $default = null ): ?string
	{
		return $_ENV[ $var ] ?? $default;
	}
}

if ( ! function_exists( 'environment' ) ) {
	/**
	 * Returns the application environment.
	 *
	 * @since  1.0.0
	 */
	function environment(): string
	{
		return env( 'APP_ENV', 'development' );
	}
}

if ( ! function_exists( 'config' ) ) {
	/**
	 * Returns a value from the configuration instance using either dot
	 * (e.g., `app.example`) or slash (e.g., `app/example`) notation.
	 *
	 * @since  1.0.0
	 * @param  string  $deprecated  Setting key. Use dot notation instead.
	 */
	function config( string $name, string $deprecated = '' ): mixed
	{
		if ( $deprecated && ! Str::contains( $name, '.' ) ) {
			$name = "{$name}.{$deprecated}";
		}

		return Config::get( $name );
	}
}

if ( ! function_exists( 'route' ) ) {
	/**
	 * Returns the named route URL.
	 *
	 * @since 1.0.0
	 */
	function route( string $name, array $params = [] ): string
	{
		return Url::route( $name, $params );
	}
}

if ( ! function_exists( 'path' ) ) {
	/**
	 * Returns app path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function path( string $append = '' ): string
	{
		return app()->path( append: $append );
	}
}

if ( ! function_exists( 'app_path' ) ) {
	/**
	 * Returns app path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function app_path( string $append = '' ): string
	{
		return app()->appPath( $append );
	}
}

if ( ! function_exists( 'config_path' ) ) {
	/**
	 * Returns public path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function config_path( string $append = '' ): string
	{
		return app()->configPath( $append );
	}
}

if ( ! function_exists( 'public_path' ) ) {
	/**
	 * Returns public path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function public_path( string $append = '' ): string
	{
		return app()->publicPath( $append );
	}
}

if ( ! function_exists( 'view_path' ) ) {
	/**
	 * Returns view path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function view_path( string $append = '' ): string
	{
		return app()->viewPath( $append );
	}
}

if ( ! function_exists( 'resource_path' ) ) {
	/**
	 * Returns resource path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function resource_path( string $append = '' ): string
	{
		return app()->resourcePath( $append );
	}
}

if ( ! function_exists( 'storage_path' ) ) {
	/**
	 * Returns storage path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function storage_path( string $append = '' ): string
	{
		return app()->storagePath( $append );
	}
}

if ( ! function_exists( 'cache_path' ) ) {
	/**
	 * Returns cache path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function cache_path( string $append = '' ): string
	{
		return app()->cachePath( $append );
	}
}

if ( ! function_exists( 'user_path' ) ) {
	/**
	 * Returns user path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function user_path( string $append = '' ): string
	{
		return app()->userPath( $append );
	}
}

if ( ! function_exists( 'content_path' ) ) {
	/**
	 * Returns content path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function content_path( string $append = '' ): string
	{
		return app()->contentPath( $append );
	}
}

if ( ! function_exists( 'media_path' ) ) {
	/**
	 * Returns media path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function media_path( string $append = '' ): string
	{
		return app()->mediaPath( $append );
	}
}

if ( ! function_exists( 'vendor_path' ) ) {
	/**
	 * Returns vendor path with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function vendor_path( string $append = '' ): string
	{
		return app()->vendorPath( $append );
	}
}

if ( ! function_exists( 'url' ) ) {
	/**
	 * Returns app URL with optional appended path. If no appended path,
	 * returns the `Url` object, which can be used as a string to get the
	 * app URL. Or, it can be used as a static class to access its methods.
	 *
	 * @since  1.0.0
	 */
	function url( string $append = '' ): Url|string
	{
		return $append ? Url::to( $append ) : Url::make();
	}
}

if ( ! function_exists( 'app_url' ) ) {
	/**
	 * Returns config URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function app_url( string $append = '' ): string
	{
		return app()->appUrl( $append );
	}
}

if ( ! function_exists( 'config_url' ) ) {
	/**
	 * Returns config URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function config_url( string $append = '' ): string
	{
		return app()->configUrl( $append );
	}
}

if ( ! function_exists( 'public_url' ) ) {
	/**
	 * Returns public URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function public_url( string $append = '' ): string
	{
		return app()->publicUrl( $append );
	}
}

if ( ! function_exists( 'view_url' ) ) {
	/**
	 * Returns view URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function view_url( string $append = '' ): string
	{
		return app()->viewUrl( $append );
	}
}

if ( ! function_exists( 'resource_url' ) ) {
	/**
	 * Returns resource URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function resource_url( string $append = '' ): string
	{
		return app()->resourceUrl( $append );
	}
}

if ( ! function_exists( 'storage_url' ) ) {
	/**
	 * Returns storage URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function storage_url( string $append = '' ): string
	{
		return app()->storageUrl( $append );
	}
}

if ( ! function_exists( 'cache_url' ) ) {
	/**
	 * Returns cache URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function cache_url( string $append = '' ): string
	{
		return app()->cacheUrl( $append );
	}
}

if ( ! function_exists( 'user_url' ) ) {
	/**
	 * Returns public URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function user_url( string $append = '' ): string
	{
		return app()->userUrl( $append );
	}
}

if ( ! function_exists( 'content_url' ) ) {
	/**
	 * Returns content URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function content_url( string $append = '' ): string
	{
		return app()->contentUrl( $append );
	}
}

if ( ! function_exists( 'media_url' ) ) {
	/**
	 * Returns media URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function media_url( string $append = '' ): string
	{
		return app()->mediaUrl( $append );
	}
}

if ( ! function_exists( 'vendor_url' ) ) {
	/**
	 * Returns vendor URL with optional appended path/file.
	 *
	 * @since 1.0.0
	 */
	function vendor_url( string $append = '' ): string
	{
		return app()->vendorUrl( $append );
	}
}

if ( ! function_exists( 'asset' ) ) {
	/**
	 * Returns an asset URI with an ID query var attached to it based on the
	 * file's last modified time. Used for cache busting. The `$path` param
	 * must be a filename relative to the public path.
	 *
	 * @since 1.0.0
	 */
	function asset( string $path ): string
	{
		$asset_url = public_url( $path );
		$modified  = filemtime( public_path( $path ) );

		return false !== $modified ? "{$asset_url}?id={$modified}" : $asset_url;
	}
}

if ( ! function_exists( 'batch' ) ) {
	/**
	 * Helper method for passing a batch of functions to apply to a variable.
	 * Functions list should be separated with a `|` pipe character. This is
	 * useful for cleaning up multiple wrappers for a variable like so:
	 * `batch( $string, 'strip_tags|strtoupper' )`.
	 *
	 * @since 1.0.0
	 */
	function batch( mixed $var, string $functions ): mixed
	{
		foreach ( explode( '|', $functions ) as $function ) {
			if ( is_callable( $function ) ) {
				$var = call_user_func( $function, $var );
			}
		}

		return $var;
	}
}

if ( ! function_exists( 'e' ) ) {
	/**
	 * Convenient wrapper around `htmlspecialchars()` for escaping strings
	 * before outputting HTML.
	 *
	 * @since 1.0.0
	 */
	function e( string $value, bool $double_encode = true ): string
	{
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8', $double_encode );
	}
}

if ( ! function_exists( 'escape_tag' ) ) {
	/**
	 * Escapes an HTML tag name for use in HTML. Note that this does not
	 * validate the tag, only makes it safe for output.
	 *
	 * @since 1.0.0
	 */
	function escape_tag( string $tag ): string
	{
		return strtolower( preg_replace( '/[^a-zA-Z0-9_:]/', '', $tag ) );
	}
}

if ( ! function_exists( 'sanitize_slug' ) ) {
	/**
	 * Sanitizes a string meant to be used as a slug.
	 *
	 * @since 1.0.0
	 */
	function sanitize_slug( string $str, string $sep = '-' ): string
	{
		return Str::slug( $str, $sep );
	}
}

if ( ! function_exists( 'cdata' ) ) {
	/**
	 * Prevents nested CDATA.
	 *
	 * @since 1.0.0
	 */
	function cdata( string $str ): string
	{
		return Str::cdata( $str );
	}
}

if ( ! function_exists( 'runt' ) ) {
	/**
	 * Ensures that the final two words of string with three or more words
	 * does not result in a runt (final word hangs on line by itself).
	 *
	 * @since 1.0.0
	 */
	function runt( string $str ): string
	{
		return Str::runt( $str );
	}
}
