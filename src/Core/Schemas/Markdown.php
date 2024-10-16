<?php
/**
 * Markdown configuration schema.
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

class Markdown
{
	/**
	 * Returns the schema structure.
	 *
	 * @since 1.0.0
	 */
	public static function schema(): Schema
	{
		return Expect::structure( [
			'config'         => Expect::array( [] ),
			'extensions'     => Expect::listOf( 'string' ),
			'inline_parsers' => Expect::listOf( 'string' )
		] );
	}
}
