<?php
/**
 * Search page controller.
 *
 * Renders `/search` with the `search` template hierarchy, falling back to
 * the page templates, so a theme without a search template still shows a
 * page titled "Search". The theme's script reads the query from the
 * address (`?q=`), loads `search_index_url()` and shows the results, so the
 * page works the same on live sites and static exports.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Content\Entry\Virtual;
use Novaris\Core\Proxies\App;
use Novaris\Search\Index;
use Novaris\Template\Tag\DocumentTitle;
use Symfony\Component\HttpFoundation\{Request, Response};

class Search extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since 1.1.2
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		if ( ! Index::enabled() ) {
			return $this->forward404( $params, $request );
		}

		$this->context( 'search' );

		$page = new Virtual( [
			'name'    => 'search',
			'url'     => \Novaris\Theme\Site\search_url(),
			'content' => '',
			'meta'    => [ 'title' => 'Search' ],
		] );

		return $this->response( $this->view(
			'index',
			[ 'search', 'page-search', 'page' ],
			[
				'doctitle'   => new DocumentTitle( $page->title() ),
				'pagination' => false,
				'page'       => $page,
				'single'     => $page,
				'entries'    => false,
				'collection' => false,
				'type'       => App::get( 'content.types' )->get( 'page' ),
			]
		) );
	}
}
