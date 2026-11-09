<?php
/**
 * Pages finder.
 *
 * Pages aren't in one folder like posts: any Markdown file outside a
 * private `_` folder is a page, such as `about/index.md` or `docs/a/b.md`.
 * This walks the content folder to find them all, for the search index and
 * the page sitemap. (The static export walks the folder the same way.)
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Content;

use Novaris\Contracts\Content\ContentEntry;
use Novaris\Core\Proxies\{App, Query};
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Pages
{
	/**
	 * Returns every page, sorted by path, including pages that aren't
	 * public, so callers can decide what to do with those.
	 *
	 * @since 1.1.4
	 * @return list<ContentEntry>
	 */
	public static function all(): array
	{
		$contentPath = App::resolve( 'path.content' );

		if ( ! is_dir( $contentPath ) ) {
			return [];
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $contentPath, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		$paths = [];

		foreach ( $files as $file ) {

			if ( ! $file->isFile() || 'md' !== strtolower( $file->getExtension() ) ) {
				continue;
			}

			$relative = str_replace( '\\', '/', substr(
				$file->getPathname(),
				strlen( rtrim( $contentPath, '/\\' ) ) + 1
			) );

			// Skip private `_` folders and files, such as `_posts`.
			foreach ( explode( '/', $relative ) as $segment ) {
				if ( str_starts_with( $segment, '_' ) ) {
					continue 2;
				}
			}

			$paths[] = preg_replace( '/\.md$/i', '', $relative );
		}

		sort( $paths );

		$pages = [];

		foreach ( $paths as $path ) {
			$directory = dirname( $path );

			$page = Query::make( [
				'path' => '.' === $directory ? '' : $directory,
				'slug' => basename( $path ),
			] )->single();

			if ( $page ) {
				$pages[] = $page;
			}
		}

		return $pages;
	}
}
