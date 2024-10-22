<?php
/**
 * Content locator interface.
 *
 * Defines the contract that content locator classes should implement.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Content;

interface ContentLocator {

	/**
	 * Sets the locator path.
	 *
	 * @since 1.0.0
	 */
	public function setPath( string $path ): void;

	/**
	 * Returns the folder path relative to the content directory.
	 *
	 * @since 1.0.0
	 */
	public function path(): string;

	/**
	 * Returns collection of located files as an array. The filenames are
	 * the array keys and the metadata is the value.
	 *
	 * @since 1.0.0
	 */
	public function all(): array;
}
