<?php
/**
 * 404 controller.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Core\Proxies\{App, Query};
use Novaris\Content\Entry\Virtual;
use Novaris\Template\Hierarchy;
use Novaris\Template\Tag\DocumentTitle;
use Symfony\Component\HttpFoundation\{Request, Response};

class Error404 extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		$single = Query::make( [
			'path' => '_error',
			'slug' => '404'
		] )->single();

		// Create a virtual entry if no user-provided entry.
		if ( ! $single ) {
			$single = new Virtual( [
				'meta'    => [ 'title' => 'Nothing Found' ],
				'content' => '<p>Sorry, nothing was found here.</p>',
			] );
		}

		return $this->response( $this->view(
			Hierarchy::error404(),
			[
				'doctitle'   => new DocumentTitle( $single->title() ),
				'pagination' => false,
				'single'     => $single,
				'collection' => false
			]
		), Response::HTTP_NOT_FOUND );
	}
}
