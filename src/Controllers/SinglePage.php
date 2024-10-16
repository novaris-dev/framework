<?php
/**
 * Single controller.
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

class SinglePage extends Single
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		$path = $params['path'] ?? '';
		$name = Str::afterLast( $path, '/' );

		// If the page name begins with `_`, it is private.
		if ( Str::startsWith( $name, '_' ) ) {
			return $this->forward404( $params, $request );
		}

		// Look for an `path/index.md` file.
		$single = Query::make( [
			'path' => $path,
			'slug' => 'index'
		] )->single();

		// Look for a `path/{$name}.md` file if `path/index.md` not found.
		if ( ! $single ) {
			$single = Query::make( [
				'path' => Str::beforeLast( $path, '/' ),
				'slug' => $name
			] )->single();
		}

		if ( $single && $single->isPublic() ) {
			$collection = false;

			if ( $args = $single->collectionArgs() ) {
				$collection = Query::make( $args );
			}

			$doctitle = new DocumentTitle( $single->title() );

			return $this->response( $this->view(
				Hierarchy::single( $single ),
				[
					'doctitle'   => $doctitle,
					'pagination' => false,
					'single'     => $single,
					'collection' => $collection
				]
			) );
		}

		// If all else fails, return a 404.
		return $this->forward404( $params, $request );
	}
}
