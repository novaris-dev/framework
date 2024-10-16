<?php
/**
 * Cache configuration schema.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core\Schemas;

use Nette\Schema\Expect;
use Nette\Schema\Schema;

class Cache
{
	/**
	 * Returns the schema structure.
	 *
	 * @since 1.0.0
	 */
	public static function schema(): Schema
	{
		return Expect::structure( [
			'purge_key'            => Expect::string( '' ),
			'expires'              => Expect::int( 0 ),
			'content_exclude_meta' => Expect::array( [] ),
			'global'               => Expect::bool( false ),
			'global_exclude'       => Expect::array( [] ),
			'stores'               => Expect::arrayOf( 'array',  'string' ),
			'drivers'              => Expect::arrayOf( 'string', 'string' ),

			// @todo - Remove. No longer in use.
			'markdown'             => Expect::bool( false )
		] );
	}
}
