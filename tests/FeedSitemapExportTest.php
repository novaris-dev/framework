<?php
/**
 * Feed, sitemap, empty term and image size tests.
 *
 * - Feeds and sitemaps render with the framework's default templates when a
 *   theme has none, and the static export writes them as `.xml` files.
 * - Terms with no entries still have their first page.
 * - Images are never enlarged to fit a size.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

use Novaris\Contracts\Content\{ContentQuery, ContentTypes};
use Novaris\Contracts\Routing\RoutingRouter;
use Novaris\Export\Exporter;
use DOMDocument;

class FeedSitemapExportTest extends TestCase
{
	/**
	 * Creates a site whose posts have a feed, with an empty category, a
	 * hidden post and a nested page.
	 */
	private function makeFeedSite( array $files = [] ): string
	{
		$types = $this->contentTypes();
		$types['post']['feed'] = true;

		return $this->makeSite( $files + [
			'config/content.php' => "<?php\nreturn " . var_export( $types, true ) . ";\n",
			'user/content/_categories/empty.md' => $this->markdown( [ 'title' => 'Empty' ] ),
			'user/content/_posts/2025-03-01.hidden.md' => $this->markdown( [
				'title'      => 'Hidden post',
				'published'  => '2025-03-01',
				'visibility' => 'hidden',
			], 'Hidden.' ),
			'user/content/docs/guide/intro.md' => $this->markdown( [ 'title' => 'Intro' ], 'Nested page.' ),
			// A page that links to the feeds and sitemap, to check the export
			// rewrites the links.
			'resources/views/index/page.php' => '<?php echo "<a href=\"" . e( $page->type()->name() === "page" ? app( "content.types" )->get( "post" )->rssFeedUrl() : "" ) . "\">RSS</a> <a href=\"" . e( url( "sitemap" ) ) . "\">Sitemap</a> <a href=\"" . e( url( "blog/feeding" ) ) . "\">Not a feed</a>";',
		] );
	}

	/**
	 * Parses XML, failing the test if it isn't well formed.
	 */
	private function xml( string $content ): DOMDocument
	{
		$document = new DOMDocument();

		$this->assertTrue( @$document->loadXML( $content ), "Valid XML:\n" . substr( $content, 0, 500 ) );

		return $document;
	}

	public function testRssFeedUsesTheDefaultTemplate(): void
	{
		$response = $this->get( $this->makeFeedSite(), '/blog/feed' );

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertStringStartsWith( '<?xml version="1.0" encoding="UTF-8"?>', $response->getContent() );

		$xml   = $this->xml( $response->getContent() );
		$items = $xml->getElementsByTagName( 'item' );

		$this->assertSame( 'rss', $xml->documentElement->tagName );
		$this->assertGreaterThan( 0, $items->length );
		$this->assertStringStartsWith( static::URL . '/blog/post-', $items->item( 0 )->getElementsByTagName( 'link' )->item( 0 )->textContent );
		$this->assertStringNotContainsString( 'Hidden post', $response->getContent() );
	}

	public function testAtomFeedUsesTheDefaultTemplate(): void
	{
		$response = $this->get( $this->makeFeedSite(), '/blog/feed/atom' );

		$xml = $this->xml( $response->getContent() );

		$this->assertSame( 'feed', $xml->documentElement->tagName );
		$this->assertGreaterThan( 0, $xml->getElementsByTagName( 'entry' )->length );
		$this->assertStringContainsString( 'rel="self" type="application/atom+xml" href="' . static::URL . '/blog/feed/atom"', $response->getContent() );
	}

	public function testThemeFeedTemplatesWin(): void
	{
		$site = $this->makeFeedSite( [
			'resources/views/feed.php' => '<?php echo "<theme-feed/>";',
		] );

		$this->assertSame( '<theme-feed/>', $this->get( $site, '/blog/feed' )->getContent() );
		$this->assertSame( '<theme-feed/>', $this->get( $site, '/blog/feed/atom' )->getContent() );
	}

	public function testSitemapsUseTheDefaultTemplates(): void
	{
		$site = $this->makeFeedSite();

		$index = $this->xml( $this->get( $site, '/sitemap' )->getContent() );
		$locs  = array_map( fn( $node ) => $node->textContent, iterator_to_array( $index->getElementsByTagName( 'loc' ) ) );

		$this->assertContains( static::URL . '/sitemap/post', $locs );
		$this->assertContains( static::URL . '/sitemap/page', $locs );

		$posts = $this->get( $site, '/sitemap/post' )->getContent();
		$this->xml( $posts );
		$this->assertStringContainsString( '<loc>' . static::URL . '/blog/post-1</loc>', $posts );
		$this->assertStringNotContainsString( 'hidden', $posts );
	}

	public function testPageSitemapListsPagesFromEveryFolder(): void
	{
		$response = $this->get( $this->makeFeedSite(), '/sitemap/page' );

		$this->assertSame( 200, $response->getStatusCode() );
		$this->xml( $response->getContent() );

		foreach ( [ '/about', '/docs/guide/intro' ] as $path ) {
			$this->assertStringContainsString( '<loc>' . static::URL . $path . '</loc>', $response->getContent(), $path );
		}

		// Private `_` folders aren't pages.
		$this->assertStringNotContainsString( '_secret', $response->getContent() );
		$this->assertStringNotContainsString( '_posts', $response->getContent() );
	}

	public function testExportWritesFeedsAndSitemapsAsXmlFiles(): void
	{
		$app = $this->boot( $this->makeFeedSite() );

		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath(),
			'https://static.example.com'
		) )->export();

		foreach ( [ 'blog/feed.xml', 'blog/feed/atom.xml', 'sitemap.xml', 'sitemap/post.xml', 'sitemap/page.xml', 'sitemap/category.xml' ] as $file ) {
			$this->assertFileExists( $app->publicPath( $file ) );
			$this->xml( file_get_contents( $app->publicPath( $file ) ) );
			$this->assertStringNotContainsString( static::URL, file_get_contents( $app->publicPath( $file ) ), $file );
		}

		// Links to feeds and sitemaps point at the `.xml` files.
		$this->assertStringContainsString( '<loc>https://static.example.com/sitemap/post.xml</loc>', file_get_contents( $app->publicPath( 'sitemap.xml' ) ) );
		$this->assertStringContainsString( 'href="https://static.example.com/blog/feed.xml" rel="self"', file_get_contents( $app->publicPath( 'blog/feed.xml' ) ) );

		$about = file_get_contents( $app->publicPath( 'about/index.html' ) );
		$this->assertStringContainsString( 'href="https://static.example.com/blog/feed.xml"', $about );
		$this->assertStringContainsString( 'href="https://static.example.com/sitemap.xml"', $about );

		// Only whole URLs are rewritten.
		$this->assertStringContainsString( 'href="https://static.example.com/blog/feeding"', $about );

		// The manifest lists the files, so the next export can clean them up.
		$manifest = json_decode( file_get_contents( $app->storagePath( 'export.json' ) ), true );
		$this->assertContains( 'blog/feed.xml', $manifest );
		$this->assertContains( 'sitemap/page.xml', $manifest );
	}

	public function testEmptyTermsHaveTheirFirstPage(): void
	{
		$site = $this->makeFeedSite();

		$this->assertSame( 200, $this->get( $site, '/category/empty' )->getStatusCode() );
		$this->assertSame( 404, $this->get( $site, '/category/empty/page/2' )->getStatusCode() );

		// Terms that don't exist are still 404.
		$this->assertSame( 404, $this->get( $site, '/category/missing' )->getStatusCode() );

		// And the export writes the empty term's page.
		$app = $this->boot( $site );

		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath()
		) )->export();

		$this->assertFileExists( $app->publicPath( 'category/empty/index.html' ) );
	}

	public function testSmallImagesAreNotEnlarged(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$site = $this->makeSite( [
			'user/content/_posts/2025-04-01.small.md' => $this->markdown( [
				'title'     => 'Small',
				'published' => '2025-04-01',
				'thumbnail' => '/user/media/small.png',
			] ),
		], [
			'supports' => [
				'featured-image' => [
					'sizes' => [
						'large'      => [ 'width' => 1366, 'height' => 768, 'crop' => true ],
						'large-soft' => [ 'width' => 1200, 'height' => 700, 'crop' => false ],
						'tiny'       => [ 'width' => 60, 'height' => 30, 'crop' => true ],
					],
				],
			],
		] );

		mkdir( "{$site}/user/media" );
		imagepng( imagecreatetruecolor( 120, 80 ), "{$site}/user/media/small.png" );

		$this->boot( $site );

		$post = app( 'content.query' )->make( [ 'path' => '_posts', 'slug' => 'small' ] )->single();

		// Cropped to the size's 16:9 shape, but not enlarged.
		$large = $post->thumbnail( 'large' );
		$this->assertLessThanOrEqual( 120, $large->width() );
		$this->assertLessThanOrEqual( 80, $large->height() );
		$this->assertEqualsWithDelta( 1366 / 768, $large->width() / $large->height(), 0.05 );

		// Not cropped: the image keeps its size.
		$soft = $post->thumbnail( 'large-soft' );
		$this->assertSame( [ 120, 80 ], [ $soft->width(), $soft->height() ] );

		// Smaller sizes still shrink and crop as before.
		$tiny = $post->thumbnail( 'tiny' );
		$this->assertSame( [ 60, 30 ], [ $tiny->width(), $tiny->height() ] );
	}
}
