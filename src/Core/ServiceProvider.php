<?php
/**
 * Base service provider.
 *
 * This is the base service provider class. This is an abstract class that must
 * be extended to create new service providers for the application.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core;

use Novaris\Contracts\Bootable;
use Novaris\Contracts\Core\Application;

abstract class ServiceProvider implements Bootable
{
	/**
	 * Accepts the application and sets it to the `$app` property.
	 *
	 * @since 1.0.0
	 */
	public function __construct( protected Application $app ) {}

	/**
	 * Callback executed when the `Application` class registers providers.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {}

	/**
	 * Callback executed after all the service providers have been registered.
	 * This is particularly useful for single-instance container objects that
	 * only need to be loaded once per page and need to be resolved early.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {}
}
