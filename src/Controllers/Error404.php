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

use Novaris\Core\Proxies\Query;
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
		// Set the current request context.
		$this->context( '404' );

		// Query the user-provided 404 entry.
		$error = Query::make( [
			'path' => '_error',
			'slug' => '404'
		] )->single();

		// Create a virtual entry if no user-provided entry exists.
		if ( ! $error ) {
			$messages = [
				'Well, this is awkward. There’s nothing here.',
				'This page wandered off somewhere.',
				'Nothing here but digital tumbleweeds.',
				'You found the void. Congratulations!',
				'This page has left the building.',
				'Looks like this page got lost somewhere in the framework.',
				'We looked everywhere. Still nothing.',
				'Plot twist: this page doesn’t exist.',
			];

			$error = new Virtual( [
				'meta' => [
					'title' => 'Nothing Found'
				],
				'content' => sprintf(
					'<p>%s</p>',
					$messages[ array_rand( $messages ) ]
				)
			] );
		}

		return $this->response( $this->view(
			'index',
			Hierarchy::error404(),
			[
				'doctitle'   => new DocumentTitle( $error->title() ),
				'pagination' => false,
				'error'      => $error,
				'collection' => false,
				'type'       => null
			]
		), Response::HTTP_NOT_FOUND );
	}
}