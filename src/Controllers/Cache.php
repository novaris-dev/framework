<?php
/**
 * Cache controller.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use Novaris\Core\Proxies\{App, Config};
use Novaris\Core\Proxies\Cache as CacheRegistry;
use Novaris\Content\Entry\Virtual;
use Novaris\Template\Tag\DocumentTitle;
use Symfony\Component\HttpFoundation\{Request, Response};

class Cache extends Controller
{
	/**
	 * Callback method when route matches request.
	 *
	 * @since  1.0.0
	 */
	public function __invoke( array $params, Request $request ): Response
	{
		$purge_key = Config::get( 'cache.purge_key' );

		$store   = $params['name'] ?? '';
		$key     = $params['key'] ?? '';
		$flushed = false;

		$title = 'Cache Flush Failure';
		$content = '<p>Invalid cache flush request.<p>';

		// Flush cache store.
		if ( $store && $key && $key === $purge_key ) {
			if ( $this->flushCacheStore( $store ) ) {
				$title = 'Cache Store Flushed';
				$content = sprintf(
					'<p>Successfully flushed and purged all data from the <code>%s</code> cache store.</p>',
					CacheRegistry::store( $store )->store()
				);
			}
		}

		// Flush all stores.
		if ( ! $store && ! $flushed && $key && $key === $purge_key ) {
			if ( $this->flushAllCacheStores() ) {
				$title = 'Cache Stores Flushed';
				$content = sprintf( '<p>Successfully flushed and purged data from all cache stores.</p>' );
			}
		}

		// Create a virtual entry for the content.
		$single = new Virtual( [
			'content' => $content,
			'meta'    => [ 'title' => $title ]
		] );

		$doctitle = new DocumentTitle( $single->title() );

		return $this->response( $this->view( [
			"single-page-cache",
			'single-page',
			'single',
			'index'
		], [
			'doctitle'   => $doctitle,
			'pagination' => false,
			'single'     => $single,
			'collection' => false
		] ) );
	}

	/**
	 * Flushes a cache store.
	 *
	 * @since 1.0.0
	 */
	private function flushCacheStore( string $name ): bool
	{
		if ( CacheRegistry::storeExists( $name ) ) {
			CacheRegistry::store( $name )->flush();
			return true;
		}

		return false;
	}

	/**
	 * Flushes all cache stores.
	 *
	 * @since 1.0.0
	 */
	private function flushAllCacheStores(): bool
	{
		CacheRegistry::purge();
		return true;
	}
}
