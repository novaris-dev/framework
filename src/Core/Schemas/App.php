<?php
/**
 * App configuration schema.
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

class App
{
	/**
	 * Returns the schema structure.
	 *
	 * @since 1.0.0
	 */
	public static function schema(): Schema
	{
		return Expect::structure( [
			'url'         => Expect::string( 'http://localhost' ),
			'title'       => Expect::string( 'Novaris' ),
			'tagline'     => Expect::string( '' ),
			'timezone'    => Expect::string( 'America/Chicago' ),
			'date_format' => Expect::string( 'F j, Y' ),
			'time_format' => Expect::string( 'g:i a' ),
			'home_alias'  => Expect::string( '' ),
			'sitemap'     => Expect::bool( false ),
			'providers'   => Expect::array( [] ),
			'proxies'     => Expect::array( [] ),

			// @deprecated 1.0.0 Soft deprecation in favor of `url`.
			'uri'         => Expect::string( '' )
		] );
	}
}
