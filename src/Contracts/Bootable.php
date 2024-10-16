<?php
/**
 * Bootable interface.
 *
 * Defines the contract that bootable classes should utilize. Bootable classes
 * should have a `boot()` method with the singular purpose of "booting" the any
 * necessary code that needs to run. Most bootable classes are meant to be
 * single-instance classes that get loaded once per page request.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts;

interface Bootable
{
	/**
	 * Bootstraps the class.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void;
}