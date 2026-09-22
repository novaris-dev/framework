<?php
/**
 * Taxonomy term controller.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Core\Proxies\{App, Query};
use Novaris\Template\Hierarchy;
use Novaris\Template\Tag\{DocumentTitle, Pagination};
use Novaris\Tools\Str;
use Symfony\Component\HttpFoundation\{Request, Response};

class Taxonomy extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		$types = App::get( 'content.types' );

		// Get needed URI params from the router.
		$name  = $params['name'];
		$path  = $params['path'] ?? '';
		$page  = intval( $params['page'] ?? 1 );
		$parts = explode( '/', $path );
		$type  = false;

		// If this is a paged view, strip the page from the path.
		if ( Str::contains( $path, "/page/{$page}" ) ) {
			$path = Str::beforeFirst( $path, "/page/{$page}" );
		}

		// Find the taxonomy type from the path or URI.
		foreach ( array_reverse( $parts ) as $part ) {
			$path = Str::beforeLast( $path, "/{$part}" );

			if ( $type = $types->getTypeFromPath( $path ) ) {
				break;
			} elseif ( $type = $types->getTypeFromUri( $path ) ) {
				break;
			}
		}

		// Bail if there is no taxonomy type.
		if ( ! $type ) {
			return $this->forward404( $params, $request );
		}

		// Query the taxonomy term.
		$single = Query::make( [
			'path' => $type->path(),
			'slug' => $name
		] )->single();

		// Get the content type collected by this taxonomy.
		$parent_type = null;
		$parent      = null;

		if ( $type->termCollect() && $types->has( $type->termCollect() ) ) {
			$parent_type = $types->get( $type->termCollect() );

			$parent = Query::make( [
				'path' => $parent_type->path(),
				'slug' => 'index'
			] )->single();
		}

		// Merge the default collection query args for the taxonomy
		// with user-defined collection args.
		$query_args = array_merge(
			$type->termCollectionArgs(),
			$single ? $single->collectionArgs() : []
		);

		// Set required variables for the query.
		$query_args['number'] = $query_args['number'] ?? 10;
		$query_args['offset'] = $query_args['number'] * ( $page - 1 );

		// Query the taxonomy term's content collection.
		$collection = Query::make( array_merge( $query_args, [
			'meta_key'   => $type->type(),
			'meta_value' => $name
		] ) );

		if ( $single && $single->isPublic() && $collection->all() ) {

			// Set the current request context.
			$this->context( 'taxonomy' );

			$doctitle = new DocumentTitle( $single->title(), [
				'page' => $page
			] );

			$pagination = new Pagination( [
				'basepath' => $path,
				'current'  => $page,
				'total'    => $collection->pages()
			] );

			return $this->response( $this->view(
				'index',
				Hierarchy::taxonomy( $single ),
				[
					'doctitle'   => $doctitle,
					'pagination' => $pagination,
					'single'     => $single,
					'collection' => $collection,
					'type'       => $type,
					'parent'     => $parent,
					'parent_type' => $parent_type
				]
			) );
		}

		// If all else fails, return a 404.
		return $this->forward404( $params, $request );
	}
}