<?php
/**
 * Post formats.
 *
 * A post format lets a theme show some posts differently, such as a quote
 * post showing just the quote, as in ClassicPress. A post sets one in its
 * front matter (`format: quote`), and a theme lists the formats it supports
 * in config/app.php:
 *
 *     'supports' => [
 *         'post-formats' => [ 'aside', 'link', 'quote' ],   // or true for all
 *     ],
 *
 * A post whose format isn't supported, or that has none, is `standard`.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Content;

use Novaris\Core\Proxies\Config;

class PostFormats
{
	/**
	 * The format of a post without one.
	 *
	 * @since 1.1.5
	 */
	public const STANDARD = 'standard';

	/**
	 * The formats a post can have, the same as ClassicPress.
	 *
	 * @since 1.1.5
	 */
	public const FORMATS = [
		'aside',
		'audio',
		'chat',
		'gallery',
		'image',
		'link',
		'quote',
		'status',
		'video',
	];

	/**
	 * Returns the formats the theme supports, from `supports.post-formats`:
	 * all of them for `true`, the known ones listed for an array, and none
	 * otherwise.
	 *
	 * @since 1.1.5
	 * @return list<string>
	 */
	public static function supported(): array
	{
		$key     = 'app.supports.post-formats';
		$support = Config::exists( $key ) ? Config::get( $key ) : false;

		if ( true === $support ) {
			return self::FORMATS;
		}

		if ( ! is_array( $support ) ) {
			return [];
		}

		$formats = array_map( fn( $format ) => is_string( $format ) ? strtolower( trim( $format ) ) : '', $support );

		return array_values( array_intersect( self::FORMATS, $formats ) );
	}

	/**
	 * Whether the theme supports a format. `standard` is always supported.
	 *
	 * @since 1.1.5
	 */
	public static function isSupported( string $format ): bool
	{
		$format = strtolower( trim( $format ) );

		return self::STANDARD === $format || in_array( $format, static::supported(), true );
	}

	/**
	 * Returns the supported format for a front matter value, or `standard`.
	 *
	 * @since 1.1.5
	 */
	public static function resolve( mixed $value ): string
	{
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( ! is_string( $value ) ) {
			return self::STANDARD;
		}

		$format = strtolower( trim( $value ) );

		return in_array( $format, static::supported(), true ) ? $format : self::STANDARD;
	}
}
