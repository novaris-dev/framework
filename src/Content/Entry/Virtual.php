<?php
/**
 * Creates a virtual content entry.
 *
 * Developers can pass an array of data to the constructor with keys matching
 * the class properties to set up a virtual entry. This is primarily useful for
 * creating the `$single` entry object for routed URIs that do not exist in the
 * filesystem.  For example, custom date-based archive pages.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Content\Entry;

use Novaris\Core\Proxies\App;
use Novaris\Contracts\Content\ContentType;

class Virtual extends Entry
{
	/**
	 * Sets up the object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $data = [] )
	{
		foreach ( array_keys( get_object_vars( $this ) ) as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$this->$key = $data[ $key ];
			}
		}
	}

	/**
	 * Returns the entry type.
	 *
	 * @since 1.0.0
	 */
	public function type(): ContentType
	{
		return App::get( 'content.types' )->get( 'virtual' );
	}

	/**
	 * Returns the entry name (slug).
	 *
	 * @since 1.0.0
	 */
	public function name(): string
	{
		return '';
	}

	/**
	 * Returns the entry URL.
	 *
	 * @since  1.0.0
	 */
	public function url(): string
	{
		return '';
	}
}