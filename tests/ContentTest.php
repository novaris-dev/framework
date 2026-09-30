<?php
/**
 * Content locator, query and content cache tests.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

class ContentTest extends TestCase
{
	/**
	 * Returns entry titles for a query on a freshly booted site.
	 */
	private function titles( string $site, array $args ): array
	{
		$query = $this->boot( $site )->make( 'content.query' )->make( $args + [ 'number' => 0 ] );

		return array_map( fn( $entry ) => $entry->title(), $query->all() );
	}

	public function testSortsMixedDateFormatsByDate(): void
	{
		$site = $this->makeSite( [
			'user/content/dated/index.md' => $this->markdown( [ 'title' => 'Dated' ] ),
			'user/content/dated/a.md'     => $this->markdown( [ 'title' => 'Jan 2024', 'published' => '2024-01-05' ] ),
			'user/content/dated/b.md'     => $this->markdown( [ 'title' => 'Mar 2024', 'published' => '"2024-03-10"' ] ),
			'user/content/dated/c.md'     => $this->markdown( [ 'title' => 'Feb 2024', 'published' => '2024-02-01 09:30:00' ] ),
			'user/content/dated/d.md'     => $this->markdown( [ 'title' => 'Jun 2024', 'published' => '2024-06-01T10:00' ] ),
			'user/content/dated/e.md'     => $this->markdown( [ 'title' => 'Dec 2023', 'published' => '"2023-12-24"' ] ),
		] );

		$this->assertSame(
			[ 'Jun 2024', 'Mar 2024', 'Feb 2024', 'Jan 2024', 'Dec 2023' ],
			$this->titles( $site, [ 'path' => 'dated', 'orderby' => 'published', 'order' => 'desc' ] )
		);

		$this->assertSame(
			[ 'Dec 2023', 'Jan 2024', 'Feb 2024', 'Mar 2024', 'Jun 2024' ],
			$this->titles( $site, [ 'path' => 'dated', 'orderby' => 'published', 'order' => 'asc' ] )
		);
	}

	public function testSkipsFilesWithInvalidFrontMatter(): void
	{
		$site = $this->makeSite( [
			'user/content/_posts/2025-01-09.broken.md' => "---\ntitle: \"Unclosed quote\nvisibility: hidden\n---\nBody",
		] );

		$log = $this->temporaryDirectory() . '/error.log';
		$old = ini_set( 'error_log', $log );

		try {
			$titles = $this->titles( $site, [ 'path' => '_posts' ] );
			$status = $this->get( $site, '/blog' )->getStatusCode();
		} finally {
			ini_set( 'error_log', $old );
		}

		$this->assertCount( 5, $titles );
		$this->assertNotContains( 'Unclosed quote', $titles );
		$this->assertSame( 200, $status );
		$this->assertStringContainsString( 'broken.md', file_get_contents( $log ) );
	}

	public function testReadsFrontMatterLargerThanFourKilobytes(): void
	{
		$meta = [ 'title' => 'Long front matter' ];

		for ( $i = 0; $i < 300; $i++ ) {
			$meta[ "field{$i}" ] = str_repeat( 'x', 20 );
		}

		// This field sits well past the first 4 KB of the file. Filtering
		// by it uses the listing metadata, not the full entry.
		$meta['published'] = '2020-06-15';

		$site = $this->makeSite( [
			'user/content/long/index.md' => $this->markdown( [ 'title' => 'Long' ] ),
			'user/content/long/entry.md' => $this->markdown( $meta, 'Body' ),
		] );

		$this->assertSame( [ 'Long front matter' ], $this->titles( $site, [ 'path' => 'long', 'year' => 2020 ] ) );

		// Metadata-only queries (used by the exporter and sitemaps) read the
		// front matter without the Markdown body.
		$this->assertSame( [ 'Long front matter' ], $this->titles( $site, [ 'path' => 'long', 'year' => 2020, 'nocontent' => true ] ) );
	}

	public function testEachQueryUsesItsOwnPath(): void
	{
		$app   = $this->boot( $this->makeSite() );
		$query = $app->make( 'content.query' );

		// Two queries made from clones share one locator, as the exporter does.
		$posts      = ( clone $query )->make( [ 'path' => '_posts', 'number' => 0 ] );
		$categories = ( clone $query )->make( [ 'path' => '_categories', 'number' => 0 ] );

		$this->assertSame( 5, $posts->count() );
		$this->assertSame( [ 'News' ], array_map( fn( $e ) => $e->title(), $categories->all() ) );
	}

	public function testDeletedFilesDisappearFromTheContentCache(): void
	{
		$site  = $this->makeSite();
		$posts = "{$site}/user/content/_posts";

		// Make every file older than the cache, so only the deletion
		// itself can mark the cache as stale.
		foreach ( glob( "{$posts}/*.md" ) as $file ) {
			touch( $file, time() - 60 );
		}

		touch( $posts, time() - 60 );

		$this->assertCount( 5, $this->titles( $site, [ 'path' => '_posts' ] ) );

		unlink( "{$posts}/2025-01-05.post-5.md" );

		$this->assertCount( 4, $this->titles( $site, [ 'path' => '_posts' ] ) );
	}

	public function testEditsInTheSameSecondRefreshTheContentCache(): void
	{
		$site  = $this->makeSite();
		$posts = "{$site}/user/content/_posts";

		// Make every file older than the cache, so only the edit itself
		// can mark the cache as stale.
		foreach ( glob( "{$posts}/*.md" ) as $file ) {
			touch( $file, time() - 60 );
		}

		touch( $posts, time() - 60 );

		$this->assertCount( 5, $this->titles( $site, [ 'path' => '_posts' ] ) );

		// Hide a post in the same second. Listings filter on cached
		// metadata, so the post only disappears if the cache refreshes.
		file_put_contents(
			"{$posts}/2025-01-05.post-5.md",
			$this->markdown( [ 'title' => 'Post 5', 'published' => '2025-01-05', 'visibility' => 'hidden' ] )
		);

		$this->assertNotContains( 'Post 5', $this->titles( $site, [ 'path' => '_posts' ] ) );
	}

	public function testRefusesPathsContainingDotDot(): void
	{
		$site = $this->makeSite( [ 'secret/notes.md' => $this->markdown( [ 'title' => 'Private notes' ] ) ] );

		$this->assertSame( [], $this->titles( $site, [ 'path' => 'about/../../../secret' ] ) );
		$this->assertSame( [], $this->titles( $site, [ 'path' => 'about\\..\\..\\..\\secret' ] ) );
	}
}
