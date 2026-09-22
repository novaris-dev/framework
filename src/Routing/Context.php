<?php
/**
 * Routing context.
 *
 * Stores information about the current request and provides methods for
 * determining what type of content is currently being rendered.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Routing;

/**
 * Routing context class.
 *
 * @since 1.0.0
 */
class Context
{
	/**
	 * Current context type.
	 *
	 * @since 1.0.0
	 */
	protected string $type = '';

	/**
	 * Sets the current context type.
	 *
	 * @since 1.0.0
	 */
	public function set( string $type ): void
	{
		$this->type = $type;
	}

	/**
	 * Gets the current context type.
	 *
	 * @since 1.0.0
	 */
	public function get(): string
	{
		return $this->type;
	}

	/**
	 * Checks whether the current context matches the given type.
	 *
	 * @since 1.0.0
	 */
	public function is( string $type ): bool
	{
		return $this->type === $type;
	}

	/**
	 * Checks whether the current request is the home page.
	 *
	 * @since 1.0.0
	 */
	public function isHome(): bool
	{
		return $this->is( 'home' );
	}

	/**
	 * Checks whether the current request is a single entry.
	 *
	 * @since 1.0.0
	 */
	public function isSingle(): bool
	{
		return $this->is( 'single' );
	}

	/**
	 * Checks whether the current request is a page.
	 *
	 * @since 1.0.0
	 */
	public function isPage(): bool
	{
		return $this->is( 'page' );
	}

	/**
	 * Checks whether the current request is a collection.
	 *
	 * @since 1.0.0
	 */
	public function isCollection(): bool
	{
		return $this->is( 'collection' );
	}

	/**
	 * Checks whether the current request is a taxonomy.
	 *
	 * @since 1.0.0
	 */
	public function isTaxonomy(): bool
	{
		return $this->is( 'taxonomy' );
	}

	/**
	 * Checks whether the current request is an archive.
	 *
	 * @since 1.0.0
	 */
	public function isArchive(): bool
	{
		return $this->is( 'archive' );
	}

	/**
	 * Checks whether the current request is a 404 page.
	 *
	 * @since 1.0.0
	 */
	public function is404(): bool
	{
		return $this->is( '404' );
	}
}