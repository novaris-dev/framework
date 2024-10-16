<?php
/**
 * Displayable contract.
 *
 * Displayable classes should implement a `display()` method. The intent of this
 * method is to output an HTML string to the screen. This data should already be
 * escaped prior to being output.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts;

interface Displayable
{
	/**
	 * Prints the HTML string.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function display(): void;
}