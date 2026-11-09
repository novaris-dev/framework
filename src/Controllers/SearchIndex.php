<?php
/**
 * Search index controller.
 *
 * Returns `/search.json`, the list of public content that themes search in
 * the browser. See `Novaris\Search\Index`.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Core\Proxies\App;
use Novaris\Search\Index;
use Symfony\Component\HttpFoundation\{Request, Response};

class SearchIndex extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.1.2
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		if ( ! Index::enabled() ) {
			return $this->forward404( $params, $request );
		}

		return new Response(
			( new Index( App::get( 'content.types' ) ) )->json(),
			Response::HTTP_OK,
			[ 'content-type' => 'application/json; charset=utf-8' ]
		);
	}
}
