<?php
/**
 * Content configuration schema.
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

class Content
{
	/**
	 * Returns the schema structure.
	 *
	 * @since 1.0.0
	 */
	public static function schema(): Schema
	{
		return Expect::arrayOf( Expect::structure( [
			'path'            => Expect::string(),
			'collect'         => Expect::type( 'string|bool' )->nullable(),
			'collection'      => Expect::array(),
			'feed'            => Expect::type( 'bool|array' )->nullable(),
			'sitemap'         => Expect::bool( true ),
			'date_archives'   => Expect::bool( false ),
			'time_archives'   => Expect::bool( false ),
			'taxonomy'        => Expect::bool( false ),
			'term_collect'    => Expect::string()->nullable(),
			'term_collection' => Expect::array(),
			'routing'         => Expect::type( 'false|array' )->default( [] ),

			// deprecated
			'url_paths'       => Expect::arrayOf( 'string', 'string' )->nullable(),
			'uri'             => Expect::string()->nullable(),
			'uri_single'      => Expect::string()->nullable(),
			'routes'          => Expect::arrayOf( 'string', 'string' )
		] ), 'string' );
	}
}
