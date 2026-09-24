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
		$types   = App::resolve( 'content.types' );
		$alias   = Config::get( 'app.home_alias' );
		$collect = null;
		$type    = null;

		// Check if homepage alias exists and if type exists.
		if ( $alias && $types->has( $alias ) ) {
			$type    = $types->get( $alias );
			$collect = $types->get( $type->collect() );
		}

		// Query if type and collection exist.
		if ( $type && $collect ) {
			$page = intval( $params['page'] ?? 1 );

			// Query homepage content type.
			$home = Query::make( [
				'path' => $type->path(),
				'slug' => 'index'
			] )->single();

			// Merge default collection query args with user-defined args.
			$query_args = array_merge(
				$type->collectionArgs(),
				$home ? $home->collectionArgs() : []
			);

			$query_args['number'] = $query_args['number'] ?? 10;
			$query_args['offset'] = $query_args['number'] * ( $page - 1 );

			$entries = Query::make( $query_args );

			// Render if home and entries exist.
			if ( $home && $entries->all() ) {

				// Set the current request context.
				$this->context( 'home' );

				$doctitle = new DocumentTitle( '', [ 'page' => $page ] );

				$pagination = new Pagination( [
					'basepath' => '',
					'current'  => $page,
					'total'    => $entries->pages()
				] );

				return $this->response( $this->view(
					'index',
					Hierarchy::collectionHome( $home ),
					[
						'doctitle'   => $doctitle,
						'pagination' => $pagination,
						'home'       => $home,
						'entries'    => $entries,
						'type'       => $type
					]
				) );
			}
		}

		// Query homepage index file.
		$home = Query::make( [ 'slug' => 'index' ] )->single();

		if ( $home && $home->isPublic() ) {

			// Set the current request context.
			$this->context( 'home' );

			$entries = $home->collectionArgs()
				? Query::make( $home->collectionArgs() )
				: null;

			return $this->response( $this->view(
				'index',
				Hierarchy::singleHome( $home ),
				[
					'doctitle'   => new DocumentTitle(),
					'pagination' => null,
					'home'       => $home,
					'entries'    => $entries,
					'type'       => null
				]
			) );
		}

		// If no index file is found, display a notice and return an empty response.
		$notice = sprintf(
			'No <code>%s</code> file found.',
			Str::appendPath( App::get( 'path.content' ), 'index.md' )
		);

		Message::make( $notice )->dump();

		return new Response( '' );
	}
}