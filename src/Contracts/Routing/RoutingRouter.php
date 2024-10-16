<?php
/**
 * Router interface.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Routing;

use Symfony\Component\HttpFoundation\{Request, Response};

interface RoutingRouter
{
	/**
	 * Returns the HTTP request.
	 *
	 * @since 1.0.0
	 */
	public function request(): Request;

	/**
	 * Returns the request path.
	 *
	 * @since 1.0.0
	 */
	public function path(): string;

	/**
	 * Returns a cached HTTP Response if global caching is enabled.  If not,
	 * returns a new HTTP Response.
	 *
	 * @since 1.0.0
	 */
	public function response(): Response;
}