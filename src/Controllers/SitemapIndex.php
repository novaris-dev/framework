<?php
/**
 * Sitemap index controller.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Core\Proxies\{App, Config, Query};
use Novaris\Content\Entry\Virtual;
use Novaris\Template\Tag\{DocumentTitle};
use Novaris\Tools\{Collection, Str};
use Symfony\Component\HttpFoundation\{Request, Response};

class SitemapIndex extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since  1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		// Get all content types.
		$types = App::get( 'content.types' );

		// Query the sitemap's index file.
		$single = Query::make( [
			'path'    => 'sitemap',
			'slug'    => 'index'
		] )->single();

		// Create a virtual entry if no user-provided entry.
		if ( ! $single ) {
			$single = new Virtual( [
				'content' => '',
				'meta'    => [ 'title' => 'Sitemap Index' ]
			] );
		}

		$sitemaps = new Collection();

		foreach ( $types as $type ) {
			$sitemaps->add( $type->name(), new class( $type ) {
				public function __construct( protected $type ) {}
				public function url(): string
				{
					return url( 'sitemap/' . $this->type->name() );
				}
			} );
		}

		if ( $single ) {

			// Get the feed view.
			return $this->response( $this->view(
				[
					'sitemap-index'
				], [
					'doctitle'   => new DocumentTitle(),
					'pagination' => false,
					'single'     => $single,
					'collection' => null,
					'sitemaps'   => $types
				]
			), Response::HTTP_OK, [ 'content-type' => 'text/xml' ] );
		}

		// If all else fails, return a 404.
		return $this->forward404( $params, $request );
	}
}
