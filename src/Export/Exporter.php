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
	 * Public directory.
	 *
	 * @since 1.0.0
	 */
	protected string $publicPath;

	/**
	 * Exported paths.
	 *
	 * @since 1.0.0
	 */
	protected array $exported = [];

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
		string $publicPath
	) {
		$this->path       = rtrim( $path, '/\\' );
		$this->publicPath = rtrim( $publicPath, '/\\' );
	}

	/**
	 * Exports the site as static HTML.
	 *
	 * @since 1.0.0
	 */
	public function export(): array
	{
		$this->exported = [];

		$this->prepareDirectory();

		// Export the homepage.
		$this->exportPath( '/' );

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
		}

		// Copy compiled public assets.
		$this->copyDirectory(
			$this->publicPath . '/assets',
			$this->path . '/public/assets'
		);

		return $this->exported;
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

			// Skip private and content-type directories.
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
		$name = $entry->name();

		if ( $type->isTaxonomy() ) {
			$path = $this->buildPath(
				$type->routingPrefix(),
				str_replace( '{name}', $name, $type->routingPaths()['single'] )
			);

			$this->exportPath( $path );
			$this->exportTaxonomyPages( $type, $entry );

			return;
		}

		$path = $this->buildPath(
			$type->routingPrefix(),
			str_replace( '{name}', $name, $type->routingPaths()['single'] )
		);

		$this->exportPath( $path );
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
				$this->buildPath( $type->routingPrefix(), $path )
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
				$this->buildPath( $type->routingPrefix(), $path )
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
			$this->exportPath(
				$this->buildPath(
					$type->routingPrefix(),
					$year
				)
			);
		}

		foreach ( array_keys( $months ) as $month ) {
			$this->exportPath(
				$this->buildPath(
					$type->routingPrefix(),
					$month
				)
			);
		}

		foreach ( array_keys( $days ) as $day ) {
			$this->exportPath(
				$this->buildPath(
					$type->routingPrefix(),
					$day
				)
			);
		}
	}

	/**
	 * Exports a single path.
	 *
	 * @since 1.0.0
	 */
	protected function exportPath( string $path ): void
	{
		$path = '/' . trim( $path, '/' );

		if ( '/' === $path ) {
			$path = '/';
		}

		if ( in_array( $path, $this->exported, true ) ) {
			return;
		}

		$request = Request::create( $path, 'GET' );
		$response = $this->router->dispatch( $request );

		if ( ! $response->isSuccessful() ) {
			return;
		}

		$this->write(
			$path,
			(string) $response->getContent()
		);

		$this->exported[] = $path;
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
	 * Prepares the export directory.
	 *
	 * @since 1.0.0
	 */
	protected function prepareDirectory(): void
	{
		if ( ! is_dir( $this->path ) ) {
			mkdir( $this->path, 0755, true );
		}
	}
}