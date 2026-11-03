<?php
/**
 * Theme name.
 *
 * Splits a theme name into the folder it lives in and the GitHub repository
 * it is installed from. A name can be a plain slug, such as `amicable`, which
 * installs from the `novaris-dev` organization, or `owner/slug`, such as
 * `someone/their-theme`, which installs from that user or organization.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Theme;

use InvalidArgumentException;

final class Name
{
	/**
	 * GitHub user or organization used when a name has no owner.
	 *
	 * @since 1.0.2
	 */
	public const DEFAULT_OWNER = 'novaris-dev';

	/**
	 * Allowed names: an optional GitHub owner (letters, numbers and single
	 * hyphens) followed by a slug (letters, numbers, `.`, `_` and `-`).
	 *
	 * @since 1.0.2
	 */
	private const PATTERN = '#^(?:[A-Za-z0-9](?:-?[A-Za-z0-9])*/)?[A-Za-z0-9][A-Za-z0-9._-]*$#';

	/**
	 * Whether a theme name is valid. Names end up in folder paths and
	 * GitHub URLs, so anything else (such as `../x`) is rejected.
	 *
	 * @since 1.0.2
	 */
	public static function isValid( string $name ): bool
	{
		return 1 === preg_match( self::PATTERN, $name )
			&& ! str_contains( $name, '..' );
	}

	/**
	 * Returns the folder name, which is the part after the owner.
	 *
	 * @since 1.0.2
	 */
	public static function slug( string $name ): string
	{
		static::validate( $name );

		return str_contains( $name, '/' )
			? substr( $name, strpos( $name, '/' ) + 1 )
			: $name;
	}

	/**
	 * Returns the `owner/slug` GitHub repository, using the default owner
	 * when the name has none.
	 *
	 * @since 1.0.2
	 */
	public static function repository( string $name ): string
	{
		static::validate( $name );

		return str_contains( $name, '/' )
			? $name
			: self::DEFAULT_OWNER . "/{$name}";
	}

	/**
	 * Throws an exception for an invalid name.
	 *
	 * @since 1.0.2
	 */
	private static function validate( string $name ): void
	{
		if ( ! static::isValid( $name ) ) {
			throw new InvalidArgumentException(
				"Invalid theme name: \"{$name}\". Use a theme slug, such as \"amicable\", or \"owner/slug\", such as \"novaris-dev/amicable\"."
			);
		}
	}
}
