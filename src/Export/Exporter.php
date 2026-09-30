<?php
/**
 * Static site exporter.
 *
 * Generates static HTML files from the Novaris site.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Export;

use Novaris\Contracts\Content\{ContentQuery, ContentTypes};
use Novaris\Contracts\Routing\RoutingRouter;
use Novaris\Core\Proxies\App;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Request;

class Exporter
{
	/**
	 * Export directory.
	 *
	 * @since 1.0.0
	 */
	protected string $path;

	/**
	 * Export URL.
	 *
	 * @since 1.0.0
	 */
	protected ?string $url;

	/**
	 * Exported paths.
	 *
	 * @since 1.0.0
	 */
	protected array $exported = [];

	/**
	 * Generated files.
	 *
	 * @since 1.0.0
	 */
	protected array $generated = [];

	/**
	 * Sets up the object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		protected RoutingRouter $router,
		protected ContentTypes $types,
		protected ContentQuery $query,
		string $path,
		?string $url = null
	) {
		$this->path = rtrim( $path, '/\\' );
		$this->url  = $url ? rtrim( $url, '/' ) : null;
	}

	/**
	 * Exports the site as static HTML.
	 *
	 * @since 1.0.0
	 */
	public function export(): array
	{
		$this->exported  = [];
		$this->generated = [];

		$this->prepareDirectory();

		// Export the homepage.
		$this->exportPath( '/' );

		// Export the 404 page.
		$this->export404();

		// Export pages.
		$this->exportPages();

		// Export each registered content type.
		foreach ( $this->types->all() as $type ) {

			if ( ! $type->isPublic() || ! $type->hasRouting() ) {
				continue;
			}

			// Pages are exported separately from the content directory.
			if ( 'page' === $type->name() ) {
				continue;
			}

			// Export the collection unless this type is the homepage.
			if ( ! $type->isHomeAlias() ) {
				$this->exportPath( $type->urlPath() );
			}

			// Query all entries for this content type.
			$query = clone $this->query;

			$query->make( [
				'type'      => $type->name(),
				'number'    => 0,
				'nocontent' => true
			] );

			// Export each entry.
			foreach ( $query as $entry ) {
				$this->exportEntry( $type, $entry );
			}

			// Export collection pagination.
			$this->exportCollectionPages( $type );

			// Export date archives.
			if ( $type->hasDateArchives() ) {
				$this->exportDateArchives( $type, $query );
			}

			// Export author archives for posts.
			if ( 'post' === $type->name() ) {
				$this->exportAuthorArchives( $type, $query );
			}
		}

		// Copy compiled theme assets.
		$theme       = config( 'app.theme' );
		$parent      = app( 'theme.metadata' )->parent();
		$asset_theme = $parent ?: $theme;

		$this->copyDirectory(
			themes_path( "{$asset_theme}/public/assets" ),
			$this->path . '/assets'
		);

		// Copy user media.
		$this->copyDirectory(
			App::resolve( 'path.media' ),
			$this->path . '/user/media'
		);

		$this->writeManifest();

		return $this->exported;
	}

	/**
	 * Exports the 404 error page.
	 *
	 * @since 1.0.0
	 */
	protected function export404(): void
	{
		$request = Request::create(
			'/__novaris_404__',
			'GET'
		);

		$response = $this->router->dispatch( $request );

		if ( 404 !== $response->getStatusCode() ) {
			return;
		}

		$content = $this->rewriteUrls(
			(string) $response->getContent()
		);

		file_put_contents(
			$this->path . '/404.html',
			$content
		);

		$this->generated[] = '404.html';
	}

	/**
	 * Exports page content.
	 *
	 * @since 1.0.0
	 */
	protected function exportPages(): void
	{
		$contentPath = App::resolve( 'path.content' );

		if ( ! is_dir( $contentPath ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$contentPath,
				RecursiveDirectoryIterator::SKIP_DOTS
			)
		);

		foreach ( $iterator as $file ) {

			if ( ! $file->isFile() ) {
				continue;
			}

			if ( 'md' !== strtolower( $file->getExtension() ) ) {
				continue;
			}

			$relative = substr(
				$file->getPathname(),
				strlen( rtrim( $contentPath, '/\\' ) ) + 1
			);

			$relative = str_replace( '\\', '/', $relative );

			// Skip private content directories.
			$segments = explode( '/', $relative );

			foreach ( $segments as $segment ) {
				if ( str_starts_with( $segment, '_' ) ) {
					continue 2;
				}
			}

			// Remove the Markdown extension.
			$relative = preg_replace(
				'/\.md$/i',
				'',
				$relative
			);

			// index.md represents its parent directory.
			if ( 'index' === basename( $relative ) ) {
				$relative = dirname( $relative );

				if ( '.' === $relative ) {
					$relative = '';
				}
			}

			$path = '/' . trim( $relative, '/' );

			// Homepage is already exported.
			if ( '/' === $path ) {
				continue;
			}

			$this->exportPath( $path );
		}
	}

	/**
	 * Exports a content entry.
	 *
	 * @since 1.0.0
	 */
	protected function exportEntry( object $type, object $entry ): void
	{
		$name  = $entry->name();
		$route = $type->routingPaths()['single'];

		// Taxonomy routes only require the entry name.
		if ( $type->isTaxonomy() ) {
			$route = str_replace(
				'{name}',
				$name,
				$route
			);

			$this->exportPath(
				$this->buildPath(
					$type->routingPrefix(),
					$route
				)
			);

			$this->exportTaxonomyPages( $type, $entry );

			return;
		}

		// Replace date placeholders when used by the route.
		if (
			str_contains( $route, '{year}' )
			|| str_contains( $route, '{month}' )
			|| str_contains( $route, '{day}' )
		) {
			$date = $entry->published();

			if ( ! $date ) {
				return;
			}

			$timestamp = is_numeric( $date )
				? (int) $date
				: strtotime( $date );

			if ( ! $timestamp ) {
				return;
			}

			$route = str_replace(
				[
					'{year}',
					'{month}',
					'{day}'
				],
				[
					date( 'Y', $timestamp ),
					date( 'm', $timestamp ),
					date( 'd', $timestamp )
				],
				$route
			);
		}

		// Replace the entry name placeholder.
		$route = str_replace(
			'{name}',
			$name,
			$route
		);

		$this->exportPath(
			$this->buildPath(
				$type->routingPrefix(),
				$route
			)
		);
	}

	/**
	 * Exports collection pagination.
	 *
	 * @since 1.0.0
	 */
	protected function exportCollectionPages( object $type ): void
	{
		$args = array_merge(
			$type->collectionArgs(),
			[
				'number'    => 0,
				'nocontent' => true
			]
		);

		$query = clone $this->query;
		$query->make( $args );

		$perPage = $type->collectionArgs()['number'] ?? 10;

		if ( 1 > $perPage ) {
			return;
		}

		$pages = (int) ceil( $query->total() / $perPage );

		for ( $page = 2; $page <= $pages; $page++ ) {
			$path = str_replace(
				'{page}',
				(string) $page,
				$type->routingPaths()['collection.paged']
			);

			$this->exportPath(
				$this->buildPath(
					$type->routingPrefix(),
					$path
				)
			);
		}
	}

	/**
	 * Exports taxonomy pagination.
	 *
	 * @since 1.0.0
	 */
	protected function exportTaxonomyPages( object $type, object $entry ): void
	{
		$args = array_merge(
			$type->termCollectionArgs(),
			[
				'meta_key'   => $type->name(),
				'meta_value' => $entry->name(),
				'number'     => 0,
				'nocontent'  => true
			]
		);

		$query = clone $this->query;
		$query->make( $args );

		$perPage = $type->termCollectionArgs()['number'] ?? 10;

		if ( 1 > $perPage ) {
			return;
		}

		$pages = (int) ceil( $query->total() / $perPage );

		for ( $page = 2; $page <= $pages; $page++ ) {
			$path = str_replace(
				[ '{name}', '{page}' ],
				[ $entry->name(), (string) $page ],
				$type->routingPaths()['single.paged']
			);

			$this->exportPath(
				$this->buildPath(
					$type->routingPrefix(),
					$path
				)
			);
		}
	}

	/**
	 * Exports date archive paths.
	 *
	 * @since 1.0.0
	 */
	protected function exportDateArchives( object $type, ContentQuery $query ): void
	{
		$years  = [];
		$months = [];
		$days   = [];

		foreach ( $query as $entry ) {

			$date = $entry->published();

			if ( ! $date ) {
				continue;
			}

			$timestamp = is_numeric( $date )
				? (int) $date
				: strtotime( $date );

			if ( ! $timestamp ) {
				continue;
			}

			$year  = date( 'Y', $timestamp );
			$month = date( 'm', $timestamp );
			$day   = date( 'd', $timestamp );

			$years[ $year ] = true;
			$months[ "{$year}/{$month}" ] = true;
			$days[ "{$year}/{$month}/{$day}" ] = true;
		}

		foreach ( array_keys( $years ) as $year ) {
			$this->exportPagedPath(
				$this->buildPath(
					$type->routingPrefix(),
					$year
				)
			);
		}

		foreach ( array_keys( $months ) as $month ) {
			$this->exportPagedPath(
				$this->buildPath(
					$type->routingPrefix(),
					$month
				)
			);
		}

		foreach ( array_keys( $days ) as $day ) {
			$this->exportPagedPath(
				$this->buildPath(
					$type->routingPrefix(),
					$day
				)
			);
		}
	}

	/**
	 * Exports author archive paths.
	 *
	 * @since 1.0.0
	 */
	protected function exportAuthorArchives( object $type, ContentQuery $query ): void
	{
		$authors = [];

		// Collect unique authors from the content entries.
		foreach ( $query as $entry ) {
			foreach ( $entry->authors() as $author ) {

				$author = sanitize_slug( (string) $author );

				if ( $author ) {
					$authors[ $author ] = true;
				}
			}
		}

		$perPage = $type->collectionArgs()['number'] ?? 10;

		foreach ( array_keys( $authors ) as $author ) {

			// Export the main author archive.
			$this->exportPath(
				$this->buildPath(
					'author',
					$author
				)
			);

			if ( 1 > $perPage ) {
				continue;
			}

			// Query all entries for this author to calculate pagination.
			$authorQuery = clone $this->query;

			$authorQuery->make( [
				'type'      => $type->name(),
				'author'    => $author,
				'number'    => 0,
				'nocontent' => true
			] );

			$pages = (int) ceil(
				$authorQuery->total() / $perPage
			);

			// Export paginated author archives.
			for ( $page = 2; $page <= $pages; $page++ ) {
				$this->exportPath(
					$this->buildPath(
						'author',
						$author,
						'page',
						(string) $page
					)
				);
			}
		}
	}

	/**
	 * Exports a single path. Returns whether the path exists, meaning it
	 * was exported now or earlier.
	 *
	 * @since 1.0.0
	 */
	protected function exportPath( string $path ): bool
	{
		$path = '/' . trim( $path, '/' );

		if ( '/' === $path ) {
			$path = '/';
		}

		if ( in_array( $path, $this->exported, true ) ) {
			return true;
		}

		$request  = Request::create( $path, 'GET' );
		$response = $this->router->dispatch( $request );

		if ( ! $response->isSuccessful() ) {
			return false;
		}

		$content = (string) $response->getContent();

		// Rewrite URLs in the exported HTML only.
		$content = $this->rewriteUrls( $content );

		$this->write(
			$path,
			$content
		);

		$this->exported[] = $path;

		return true;
	}

	/**
	 * Exports a path and each of its `/page/{n}` pages, stopping at the
	 * first page that doesn't exist.
	 *
	 * @since 1.0.0
	 */
	protected function exportPagedPath( string $path ): void
	{
		if ( ! $this->exportPath( $path ) ) {
			return;
		}

		// The limit only guards against a route that never returns a 404.
		for ( $page = 2; $page <= 1000; $page++ ) {
			if ( ! $this->exportPath( rtrim( $path, '/' ) . "/page/{$page}" ) ) {
				break;
			}
		}
	}

	/**
	 * Rewrites URLs for the static export.
	 *
	 * @since 1.0.0
	 */
	protected function rewriteUrls( string $content ): string
	{
		$runtimeUrl = rtrim(
			(string) config( 'app.url', '' ),
			'/'
		);

		// Rewrite asset URLs to the exported asset directory. This covers
		// theme assets (`/themes/{theme}/public/assets/`) and private app
		// assets (`/public/assets/`), which are both exported to `/assets/`.
		$assetUrl = $this->url
			? $this->url . '/assets/'
			: '/assets/';

		// Only match URLs that belong to this site: the full site URL
		// (e.g. `https://example.com/blog`) or its path (e.g. `/blog`).
		$basePath = rtrim(
			(string) parse_url( $runtimeUrl, PHP_URL_PATH ),
			'/'
		);

		$prefix = $runtimeUrl
			? '(?:' . preg_quote( $runtimeUrl, '#' ) . '|' . preg_quote( $basePath, '#' ) . ')'
			: '';

		// The URL must start at the beginning of an attribute value, CSS
		// `url()`, or after whitespace, so the match can never reach back
		// across other markup on the page.
		$content = preg_replace(
			'#(?<![^\s"\'(=,])' . $prefix . '(?:/themes/[^/\s"\'<>]+)?/public/assets/#',
			$assetUrl,
			$content
		);

		if (
			! $this->url
			|| ! $runtimeUrl
			|| $runtimeUrl === $this->url
		) {
			return $content;
		}

		return str_replace(
			$runtimeUrl,
			$this->url,
			$content
		);
	}

	/**
	 * Writes rendered HTML to the export directory.
	 *
	 * @since 1.0.0
	 */
	protected function write( string $path, string $content ): void
	{
		$directory = '/' === $path
			? $this->path
			: $this->path . '/' . trim( $path, '/' );

		if ( ! is_dir( $directory ) ) {
			mkdir( $directory, 0755, true );
		}

		file_put_contents(
			$directory . '/index.html',
			$content
		);

		$relative = '/' === $path
			? 'index.html'
			: trim( $path, '/' ) . '/index.html';

		$this->generated[] = $relative;
	}

	/**
	 * Copies a directory recursively.
	 *
	 * @since 1.0.0
	 */
	protected function copyDirectory( string $source, string $destination ): void
	{
		if ( ! is_dir( $source ) ) {
			return;
		}

		if ( ! is_dir( $destination ) ) {
			mkdir( $destination, 0755, true );
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$source,
				RecursiveDirectoryIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {

			$target = $destination . '/' . $iterator->getSubPathName();

			if ( $item->isDir() ) {

				if ( ! is_dir( $target ) ) {
					mkdir( $target, 0755, true );
				}

				continue;
			}

			copy(
				$item->getPathname(),
				$target
			);
		}
	}

	/**
	 * Builds a URL path.
	 *
	 * @since 1.0.0
	 */
	protected function buildPath( string ...$parts ): string
	{
		$parts = array_filter(
			array_map(
				fn( $part ) => trim( $part, '/' ),
				$parts
			)
		);

		return implode( '/', $parts );
	}

	/**
	 * Returns the export manifest filepath.
	 *
	 * @since 1.0.0
	 */
	protected function manifestPath(): string
	{
		return App::resolve( 'path.storage' ) . '/export.json';
	}

	/**
	 * Writes the generated file manifest.
	 *
	 * @since 1.0.0
	 */
	protected function writeManifest(): void
	{
		$filepath  = $this->manifestPath();
		$directory = dirname( $filepath );

		if ( ! is_dir( $directory ) ) {
			mkdir( $directory, 0755, true );
		}

		file_put_contents(
			$filepath,
			json_encode(
				array_values( array_unique( $this->generated ) ),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			)
		);
	}

	/**
	 * Prepares the export directory.
	 *
	 * Removes only files generated by the previous export.
	 *
	 * @since 1.0.0
	 */
	protected function prepareDirectory(): void
	{
		if ( ! is_dir( $this->path ) ) {
			mkdir( $this->path, 0755, true );

			return;
		}

		$manifest = $this->manifestPath();

		if ( ! is_file( $manifest ) ) {
			return;
		}

		$content = file_get_contents( $manifest );
		$files   = $content ? json_decode( $content, true ) : null;

		if ( ! is_array( $files ) ) {
			return;
		}

		$directories = [];

		foreach ( $files as $relative ) {

			if ( ! is_string( $relative ) || '' === $relative ) {
				continue;
			}

			$relative = str_replace( '\\', '/', $relative );

			if (
				str_starts_with( $relative, '/' )
				|| in_array( '..', explode( '/', $relative ), true )
			) {
				continue;
			}

			$filepath = $this->path . '/' . $relative;

			if ( is_file( $filepath ) ) {
				unlink( $filepath );
			}

			$directory = dirname( $filepath );

			while (
				$directory !== $this->path
				&& str_starts_with( $directory, $this->path . DIRECTORY_SEPARATOR )
			) {
				$directories[ $directory ] = strlen( $directory );
				$directory = dirname( $directory );
			}
		}

		arsort( $directories );

		foreach ( array_keys( $directories ) as $directory ) {

			if ( ! is_dir( $directory ) ) {
				continue;
			}

			$contents = array_diff(
				scandir( $directory ) ?: [],
				[ '.', '..' ]
			);

			if ( ! $contents ) {
				rmdir( $directory );
			}
		}
	}

}