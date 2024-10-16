<?php
/**
 * Home controller.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Core\Proxies\{App, Config, Message, Query};
use Novaris\Template\Hierarchy;
use Novaris\Template\Tag\{DocumentTitle, Pagination};
use Novaris\Tools\Str;
use Symfony\Component\HttpFoundation\{Request, Response};

class Home extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		$types = App::resolve( 'content.types' );
		$alias = Config::get( 'app.home_alias' );
		$type  = false;

		// Checks if the homepage has an alias content type and if the
		// type exists.
		if ( $alias && $types->has( $alias ) ) {
			$type    = $types->get( $alias );
			$collect = $types->get( $type->collect() );
		}

		// If we have a content type and a collection type, run query.
		if ( $type && $collect ) {
			$page = intval( $params['page'] ?? 1 );

			// Query the content type.
			$single = Query::make( [
				'path' => $type->path(),
				'slug' => 'index'
			] )->single();

			// Merge the default collection query args for the type
			// with user query args.
			$query_args = array_merge(
				$type->collectionArgs(),
				$single ? $single->collectionArgs() : []
			);

			// Set required variables for the query.
			$query_args['number'] = $query_args['number'] ?? 10;
			$query_args['offset'] = $query_args['number'] * ( $page - 1 );

			// Query the content type collection.
			$collection = Query::make( $query_args );

			if ( $single && $collection->all() ) {
				$type_name = sanitize_slug( $type->type() );

				$doctitle = new DocumentTitle( '', [
					'page' => $page
				] );

				$pagination = new Pagination( [
					'basepath' => '',
					'current'  => $page,
					'total'    => $collection->pages()
				] );

				return $this->response( $this->view(
					Hierarchy::collectionHome( $single ),
					[
						'doctitle'   => $doctitle,
						'pagination' => $pagination,
						'single'     => $single,
						'collection' => $collection
					]
				) );
			}
		}

		// Query the homepage `index.md` file.
		$single = Query::make( [ 'slug' => 'index' ] )->single();

		if ( $single && $single->isPublic() ) {
			$collection = false;

			if ( $args = $single->collectionArgs() ) {
				$collection = Query::make( $args );
			}

			return $this->response( $this->view(
				Hierarchy::singleHome( $single ),
				[
					'doctitle'   => new DocumentTitle(),
					'pagination' => false,
					'single'     => $single,
					'collection' => $collection
				]
			) );
		}

		// If no index file is found, which is the minimum necessary for
		// a site, we'll dump a notice and return an empty response.
		// Note that this is not a 404. It is a user error.
		$notice = sprintf(
			'No <code>%s</code> file found.',
			Str::appendPath( App::get( 'path.content' ), 'index.md' )
		);

		Message::make( $notice )->dump();

		return new Response( '' );
	}
}
