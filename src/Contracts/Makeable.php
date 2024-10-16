<?php
/**
 * Makeable interface.
 *
 * Defines the contract that makeable classes should utilize. Makeable classes
 * should have a `make()` method for creating or building all or part of the
 * object and should always return the object itself for chaining methods.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts;

interface Makeable
{
	/**
	 * Makes an object.
	 *
	 * @since 1.0.0
	 */
	public function make(): self;
}