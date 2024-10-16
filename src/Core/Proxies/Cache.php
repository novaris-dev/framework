<?php
/**
 * Content query static proxy class.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core\Proxies;

use Novaris\Core\Proxy;
use Novaris\Cache\Registry;

class Cache extends Proxy
{
	/**
	 * Returns the name of the accessor for object registered in the container.
	 *
	 * @since 1.0.0
	 */
	protected static function accessor(): string
	{
		return 'cache.registry';
	}
}
