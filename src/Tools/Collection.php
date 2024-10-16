<?php
/**
 * Collection class.
 *
 * This file houses the `Collection` class, which is a class used for storing
 * collections of data.  Generally speaking, it was built for storing an
 * array of key/value pairs.  Values can be any type of value.  Keys should
 * be named rather than numeric if you need easy access.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tools;

use ArrayObject;
use JsonSerializable;

class Collection extends ArrayObject implements JsonSerializable
{
	/**
	 * Add an item.
	 *
	 * @since  1.0.0
	 */
	public function add( mixed $name, mixed $value ): void
	{
		$this->offsetSet( $name, $value );
	}

	/**
	 * Removes an item.
	 *
	 * @since  1.0.0
	 */
	public function remove( mixed $name ): void
	{
		$this->offsetUnset( $name );
	}

	/**
	 * Checks if an item exists.
	 *
	 * @since  1.0.0
	 */
	public function has( mixed $name ): bool
	{
		return $this->offsetExists( $name );
	}

	/**
	 * Returns an item.
	 *
	 * @since  1.0.0
	 */
	public function get( mixed $name )
	{
		return $this->offsetGet( $name );
	}

	/**
	 * Returns the collection of items.
	 *
	 * @since 1.0.0
	 */
	public function all(): array
	{
		return $this->getArrayCopy();
	}

	/**
	 * Magic method when trying to set a property. Assume the property is
	 * part of the collection and add it.
	 *
	 * @since  1.0.0
	 */
	public function __set( string $name, mixed $value ): void
	{
		$this->offsetSet( $name, $value );
	}

	/**
	 * Magic method when trying to unset a property.
	 *
	 * @since  1.0.0
	 */
	public function __unset( string $name ): void
	{
		$this->offsetUnset( $name );
	}

	/**
	 * Magic method when trying to check if a property has.
	 *
	 * @since  1.0.0
	 */
	public function __isset( string $name ): bool
	{
		return $this->offsetExists( $name );
	}

	/**
	 * Magic method when trying to get a property.
	 *
	 * @since  1.0.0
	 */
	public function __get( string $name ): mixed
	{
		return $this->offSetGet( $name );
	}

	/**
	 * Returns a JSON-ready array of data.
	 *
	 * @since  1.0.0
	 */
	public function jsonSerialize(): mixed
	{
		return array_map( function( $value ) {

			if ( $value instanceof JsonSerializable ) {
				return $value->jsonSerialize();
			}

			return $value;

		}, $this->all() );
	}
}
