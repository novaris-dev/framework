<?php
/**
 * Static proxy class.
 *
 * The base static proxy class. This allows us to create easy-to-use, static
 * classes around shared objects in the container.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core;

use Novaris\Contracts\Core\Container;

class Proxy
{
	/**
	 * The container object.
	 *
	 * @since 1.0.0
	 */
	protected static Container $container;

	/**
	 * Returns the name of the accessor for object registered in the container.
	 *
	 * @since  1.0.0
	 */
	protected static function accessor(): string
	{
		return '';
	}

	/**
	 * Sets the container object.
	 *
	 * @since 1.0.0
	 */
	public static function setContainer( Container $container ): void
	{
		static::$container = $container;
	}

	/**
	 * Returns the instance from the container.
	 *
	 * @since 1.0.0
	 */
	protected static function instance(): object
	{
		return static::$container->resolve( static::accessor() );
	}

	/**
	 * Calls the requested method from the object registered with the
	 * container statically.
	 *
	 * @since  1.0.0
	 */
	public static function __callStatic( string $method, array $args ): mixed
	{
		$instance = static::instance();
		return $instance ? $instance->$method( ...$args ) : null;
	}
}
