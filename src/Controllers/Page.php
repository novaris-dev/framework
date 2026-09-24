<?php
/**
 * Page controller.
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
use Novaris\Template\Tag\DocumentTitle;
use Novaris\Tools\Str;
use Symfony\Component\HttpFoundation\{Request, Response};

class Page extends Single
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		$types = App::resolve( 'content.types' );
		$type  = $types->get( 'page' );

		$path = $params['path'] ?? '';
		$name = Str::afterLast( $path, '/' );

		// If the page name begins with `_`, it is private.
		if ( Str::startsWith( $name, '_' ) ) {
			return $this->forward404( $params, $request );
		}

		// Look for an `path/index.md` file.
		$page = Query::make( [
			'path' => $path,
			'slug' => 'index'
		] )->single();

		// Look for a `path/{$name}.md` file if `path/index.md` not found.
		if ( ! $page ) {
			$page = Query::make( [
				'path' => Str::beforeLast( $path, '/' ),
				'slug' => $name
			] )->single();
		}

		if ( $page && $page->isPublic() ) {

			// Set the current request context.
			$this->context( 'page' );

			$entries = false;

			if ( $args = $page->collectionArgs() ) {
				$entries = Query::make( $args );
			}

			$doctitle = new DocumentTitle( $page->title() );

			return $this->response( $this->view(
				'index',
				Hierarchy::page( $page ),
				[
					'doctitle'   => $doctitle,
					'pagination' => false,
					'page'       => $page,
					'entries'    => $entries,
					'type'       => $type
				]
			) );
		}

		// If all else fails, return a 404.
		return $this->forward404( $params, $request );
	}
}