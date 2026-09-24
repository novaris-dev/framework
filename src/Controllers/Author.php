<?php
/**
 * Author archive controller.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Content\Entry\Virtual;
use Novaris\Core\Proxies\{App, Query};
use Novaris\Template\Hierarchy;
use Novaris\Template\Tag\{DocumentTitle, Pagination};
use Symfony\Component\HttpFoundation\{Request, Response};

class Author extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		$author = $params['author'] ?? '';
		$page   = intval( $params['page'] ?? 1 );

		// If there is no author, bail early.
		if ( ! $author ) {
			return $this->forward404( $params, $request );
		}

		// Get the post content type.
		$types = App::resolve( 'content.types' );
		$type  = $types->get( 'post' );

		if ( ! $type ) {
			return $this->forward404( $params, $request );
		}

		// Get the content type collection vars.
		$query_args = $type->collectionArgs();

		// Set required variables for the query.
		$query_args['number'] = $query_args['number'] ?? 10;
		$query_args['offset'] = $query_args['number'] * ( $page - 1 );
		$query_args['author'] = $author;

		// Create a virtual entry for the author archive.
		$single = new Virtual( [
			'content' => '',
			'meta'    => [
				'title' => $author
			]
		] );

		// Query the author collection.
		$collection = Query::make( $query_args );

		if ( $collection->all() ) {

			// Set the current request context.
			$this->context( 'author' );

			$doctitle = new DocumentTitle( $single->title(), [
				'page' => $page
			] );

			$pagination = new Pagination( [
				'basepath' => "author/{$author}",
				'current'  => $page,
				'total'    => $collection->pages()
			] );

			return $this->response( $this->view(
				'index',
				Hierarchy::author( $author ),
				[
					'doctitle'   => $doctitle,
					'pagination' => $pagination,
					'single'     => $single,
					'collection' => $collection,
					'type'       => $type
				]
			) );
		}

		// If all else fails, return a 404.
		return $this->forward404( $params, $request );
	}
}