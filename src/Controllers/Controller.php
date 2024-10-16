<?php
/**
 * Base controller class.
 *
 * Controllers are the bridge between the the HTTP request and what users see in
 * the browser.  This is the base class that all other controllers should use.
 * The `__invoke()` method is the only method required and should return a
 * `Response` back.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

// Abstracts.
use Novaris\Contracts\Template\TemplateView;

// Concretes.
use Novaris\Core\Proxies\Engine;
use Symfony\Component\HttpFoundation\{Request, Response};

abstract class Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		return $this->response( $this->view( 'index' ) );
	}

	/**
	 * Wrapper for the template engine view class.
	 *
	 * @since  1.0.0
	 */
	protected function view( string|array $names, Collection|array $data = [] ): TemplateView
	{
		return Engine::first( (array) $names, $data );
	}

	/**
	 * Wrapper for sending a new response to the browser.
	 *
	 * @since 1.0.0
	 */
	protected function response( TemplateView $view, int $status = 200, array $headers = [] ): Response
	{
		return new Response( $view->render(), $status, $headers );
	}

	/**
	 * Forwards request to another controller.
	 *
	 * @since 1.0.0
	 */
	protected function forward( string $callback, array $params, Request $request ): Response
	{
		$controller = new $callback;
		return $controller( $params, $request );
	}

	/**
	 * Forwards request to the `Error404` controller.
	 *
	 * @since 1.0.0
	 */
	protected function forward404( array $params, Request $request ): Response
	{
		return $this->forward( Error404::class, $params, $request );
	}
}
