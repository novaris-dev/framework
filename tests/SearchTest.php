<?php
/**
 * Search tests.
 *
 * `supports.search` in config/app.php turns on `/search.json`, the index
 * that themes search in the browser, and the `/search` page.
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

use function Novaris\Theme\Site\{search_enabled, search_index_url, search_url};

class SearchTest extends TestCase
{
	/**
	 * Content for the search tests, on top of the default test content.
	 */
	private function searchContent(): array
	{
		return [
			'user/content/_posts/2025-02-01.markup.md' => $this->markdown( [
				'title'     => 'Markup & "Quotes"',
				'published' => '2025-02-01',
				'category'  => [ 'news' ],
			], "A **bold** word, a [link](/about) and &amp; an entity.\n\n- One\n- Two\n\n> Quoted" ),
			'user/content/_posts/2025-02-02.hidden.md' => $this->markdown( [
				'title'      => 'Hidden post',
				'published'  => '2025-02-02',
				'visibility' => 'hidden',
			], 'Should not be searchable.' ),
			'user/content/_posts/2025-02-03.japanese.md' => $this->markdown( [
				'title'     => '日本語の投稿',
				'published' => '2025-02-03',
			], 'これは日本語のテストです。' ),
			'user/content/parent/child.md' => $this->markdown( [ 'title' => 'Child page' ], 'A nested page.' ),
		];
	}

	/**
	 * Creates a site with search set to `$search`.
	 */
	private function makeSearchSite( mixed $search = true, array $files = [], array $types = [] ): string
	{
		$files = $this->searchContent() + $files;

		if ( $types ) {
			$files['config/content.php'] = "<?php\nreturn " . var_export( array_replace_recursive( $this->contentTypes(), $types ), true ) . ";\n";
		}

		return $this->makeSite( $files, [ 'supports' => [ 'search' => $search ] ] );
	}

	/**
	 * Returns the decoded `/search.json` entries, keyed by URL path.
	 */
	private function index( string $site ): array
	{
		$response = $this->get( $site, '/search.json' );

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'application/json; charset=utf-8', $response->headers->get( 'Content-Type' ) );

		$data = json_decode( $response->getContent(), true );

		$this->assertIsArray( $data['entries'] ?? null );

		$entries = [];

		foreach ( $data['entries'] as $entry ) {
			$entries[ substr( $entry['url'], strlen( static::URL ) ) ?: '/' ] = $entry;
		}

		return $entries;
	}

	public function testSearchIsOffByDefault(): void
	{
		$site = $this->makeSite( $this->searchContent() );
		$this->boot( $site );

		$this->assertFalse( search_enabled() );
		$this->assertSame( 404, $this->get( $site, '/search.json' )->getStatusCode() );
		$this->assertSame( 404, $this->get( $site, '/search' )->getStatusCode() );
	}

	public function testOnlyTrueOrAnArrayTurnsSearchOn(): void
	{
		foreach ( [ false, null, 0, 'yes' ] as $value ) {
			$site = $this->makeSearchSite( $value );
			$this->boot( $site );

			$this->assertFalse( search_enabled(), var_export( $value, true ) );
			$this->assertSame( 404, $this->get( $site, '/search.json' )->getStatusCode(), var_export( $value, true ) );
		}

		foreach ( [ true, [], [ 'length' => 100 ] ] as $value ) {
			$this->boot( $this->makeSearchSite( $value ) );
			$this->assertTrue( search_enabled(), var_export( $value, true ) );
		}
	}

	public function testIndexListsPublicPostsAndPages(): void
	{
		$entries = $this->index( $this->makeSearchSite() );

		// Posts and pages, including nested pages and the home page.
		foreach ( [ '/blog/post-1', '/blog/markup', '/blog/japanese', '/about', '/parent/child', '/' ] as $path ) {
			$this->assertArrayHasKey( $path, $entries, $path );
		}

		$post = $entries['/blog/post-1'];
		$this->assertSame( 'Post 1', $post['title'] );
		$this->assertSame( 'post', $post['type'] );
		$this->assertSame( '2025-01-01', $post['date'] );
		$this->assertSame( [ 'category' => [ 'News' ] ], $post['terms'] );
		$this->assertSame( 'Body 1', $post['text'] );
		$this->assertStringStartsWith( 'Body 1', $post['excerpt'] );

		$this->assertSame( 'page', $entries['/parent/child']['type'] );
		$this->assertSame( 'A nested page.', $entries['/parent/child']['text'] );

		// Non-Latin text is kept as written.
		$this->assertSame( '日本語の投稿', $entries['/blog/japanese']['title'] );
		$this->assertSame( 'これは日本語のテストです。', $entries['/blog/japanese']['text'] );
	}

	public function testIndexLeavesOutWhatIsNotPublic(): void
	{
		$entries = $this->index( $this->makeSearchSite() );
		$urls    = implode( ' ', array_keys( $entries ) );

		// Hidden posts.
		$this->assertArrayNotHasKey( '/blog/hidden', $entries );

		// Private content types. (`drafts` is public; it only leaves the
		// sitemap, so it stays searchable.)
		$this->assertStringNotContainsString( 'secret', strtolower( $urls ) );
		$this->assertArrayHasKey( '/_drafts/one', $entries );

		// Taxonomies are left out by default.
		$this->assertArrayNotHasKey( '/category/news', $entries );

		foreach ( $entries as $entry ) {
			$this->assertNotContains( $entry['type'], [ 'category', 'secret' ] );
		}
	}

	public function testIndexTextIsPlainText(): void
	{
		$entry = $this->index( $this->makeSearchSite() )['/blog/markup'];

		$this->assertSame( 'Markup & "Quotes"', $entry['title'] );

		// No Markdown or HTML, entities decoded, and list items kept apart.
		$this->assertSame( 'A bold word, a link and & an entity. One Two Quoted', $entry['text'] );
		$this->assertStringNotContainsString( '<', $entry['excerpt'] );
	}

	public function testContentTypesCanBeLeftOutOrAdded(): void
	{
		$entries = $this->index( $this->makeSearchSite( true, [], [
			'post'     => [ 'search' => false ],
			'category' => [ 'search' => true ],
		] ) );

		$this->assertArrayNotHasKey( '/blog/post-1', $entries );
		$this->assertArrayHasKey( '/about', $entries );
		$this->assertArrayHasKey( '/category/news', $entries );
	}

	public function testLengthOptionLimitsTheText(): void
	{
		$site = $this->makeSearchSite( [ 'length' => 6 ], [
			'user/content/_posts/2025-03-01.long.md' => $this->markdown( [ 'title' => 'Long', 'published' => '2025-03-01' ], 'Abcdef ghijkl mnopqr' ),
		] );

		$this->assertSame( 'Abcdef', $this->index( $site )['/blog/long']['text'] );
	}

	public function testSearchPageFallsBackToThePageTemplate(): void
	{
		$site = $this->makeSearchSite( true, [
			'resources/views/index/page.php' => '<?php echo "page template: " . e( $page->title() ) . ( is_search() ? " (search)" : "" );',
		] );

		$response = $this->get( $site, '/search?q=bold' );

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'page template: Search (search)', $response->getContent() );
	}

	public function testThemesCanProvideASearchTemplate(): void
	{
		$site = $this->makeSearchSite( true, [
			'resources/views/index/search.php' => '<?php echo "search template, index at " . \Novaris\Theme\Site\search_index_url() . ", query " . \Novaris\Theme\Site\search_query();',
		] );

		$_GET = [ 'q' => ' bold ' ];

		try {
			$this->assertSame(
				'search template, index at ' . static::URL . '/search.json, query bold',
				$this->get( $site, '/search?q=bold' )->getContent()
			);
		} finally {
			$_GET = [];
		}
	}

	public function testUrlHelpers(): void
	{
		$this->boot( $this->makeSearchSite() );

		$this->assertSame( static::URL . '/search', search_url() );
		$this->assertSame( static::URL . '/search?q=caf%C3%A9%20%26%20more', search_url( 'café & more' ) );
		$this->assertSame( static::URL . '/search.json', search_index_url() );
	}

	public function testExportWritesTheSearchPageAndIndex(): void
	{
		$app = $this->boot( $this->makeSearchSite() );

		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath(),
			'https://static.example.com'
		) )->export();

		$this->assertFileExists( $app->publicPath( 'search/index.html' ) );
		$this->assertFileExists( $app->publicPath( 'search.json' ) );

		// URLs in the index use the export's URL.
		$data = json_decode( file_get_contents( $app->publicPath( 'search.json' ) ), true );
		$urls = array_column( $data['entries'], 'url' );

		$this->assertContains( 'https://static.example.com/blog/post-1', $urls );
		$this->assertStringNotContainsString( static::URL, file_get_contents( $app->publicPath( 'search.json' ) ) );

		// The export manifest lists it, so the next export can clean it up.
		$this->assertContains( 'search.json', json_decode( file_get_contents( $app->storagePath( 'export.json' ) ), true ) );
	}

	public function testExportWithoutSearchWritesNoSearchFiles(): void
	{
		$app = $this->boot( $this->makeSite() );

		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath()
		) )->export();

		$this->assertFileDoesNotExist( $app->publicPath( 'search.json' ) );
		$this->assertFileDoesNotExist( $app->publicPath( 'search/index.html' ) );
	}
}
