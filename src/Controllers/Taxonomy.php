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

class Taxonomy extends Controller {

	/**
	 * Callback method when route matches request.
	 *
	 * @since  1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response {

		$types = App::get( 'content.types' );

		// URL parameters
		$path = $params['path'];   // e.g., 'category/my-category'
		$name = $params['name'];   // e.g., 'my-category'
		$page = intval($params['page'] ?? 1);

		// Extract the prefix from the URL (e.g., 'category')
		$type_path = Str::beforeLast( $path, "/{$name}" );

		// Default behavior: 'category' and 'tag'
		if ( $type_path === 'category' ) {
			$type = $types->getTypeFromPath( 'category' );  // Use 'category' by default
		} elseif ( $type_path === 'tag' ) {
			$type = $types->getTypeFromPath( 'tag' );  // Use 'tag' by default
		}

		// Custom routing: '_categories' and '_tags'
		if ( $type_path === 'category' || $type_path === 'topics' ) {
			$type = $types->getTypeFromPath( '_categories' );  // Custom routing for categories
		} elseif ( $type_path === 'tag' ) {
			$type = $types->getTypeFromPath( '_tags' );  // Custom routing for tags
		}

		// Fallback: If none is found, fallback to $type_path
		if ( ! $type ) {
			$type = $types->getTypeFromPath( $type_path );
		}

		if (Str::contains( $path, "/page/{$page}" ) ) {
			$path = Str::beforeFirst( $path, "/page/{$page}" );
		}

		if (!$type) {
			return $this->forward404( $params, $request );  // Handle 404 if type is not found
		}

		// Fetch the content type it collects (e.g., 'post')
		$collect = $types->get( $type->termCollect() );

		// Query the taxonomy term
		$single = Query::make( [
			'path' => $type->path(),
			'slug' => $name
		] )->single();

		// Merge the default collection query args for the type with user query args
		$query_args = array_merge(
			$type->termCollectionArgs(),
			$single ? $single->collectionArgs() : []
		);

		// Set required variables for the query
		$query_args['number'] = $query_args['number'] ?? 10;
		$query_args['offset'] = $query_args['number'] * ($page - 1);

		// Query the term's content collection
		$collection = Query::make( array_merge( $query_args, [
			'meta_key'   => $type->type(),
			'meta_value' => $name
		] ) );

		if ( $single && $single->isPublic() && $collection->all() ) {
			$type_name = sanitize_slug( $type->type() );

			$doctitle = new DocumentTitle( $single->title(), [
				'page' => $page
			]);

			$pagination = new Pagination( [
				'basepath' => $path,
				'current'  => $page,
				'total'    => $collection->pages()
			] );

			return $this->response( $this->view(
				Hierarchy::taxonomy($single), [
					'doctitle'   => $doctitle,
					'pagination' => $pagination,
					'single'     => $single,
					'collection' => $collection
				]
			) );
		}

		// If all else fails, return a 404
		return $this->forward404( $params, $request );
	}

}
