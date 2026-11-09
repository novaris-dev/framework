<?php
/**
 * Post format tests.
 *
 * A post sets `format:` in its front matter, and the theme lists the formats
 * it supports with `supports.post-formats` in config/app.php.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

use Novaris\Content\PostFormats;
use Novaris\Template\Hierarchy;

use function Novaris\Theme\Site\post_formats;

class PostFormatTest extends TestCase
{
	/**
	 * Posts with formats, on top of the default test content.
	 */
	private function formatPosts(): array
	{
		$post = fn( string $slug, string $date, array $meta = [] ) => [
			"user/content/_posts/{$date}.{$slug}.md" => $this->markdown( [ 'title' => ucfirst( $slug ), 'published' => $date ] + $meta, "Body of {$slug}." ),
		];

		return $post( 'quoted', '2025-05-01', [ 'format' => 'quote' ] )
			+ $post( 'linked', '2025-05-02', [ 'format' => 'Link' ] )
			+ $post( 'aside', '2025-05-03', [ 'format' => 'aside' ] )
			+ $post( 'unknown', '2025-05-04', [ 'format' => 'banana' ] )
			+ $post( 'plain', '2025-05-05' );
	}

	/**
	 * Boots a site with `supports.post-formats` set to `$formats`.
	 */
	private function bootWith( mixed $formats, array $files = [] ): string
	{
		$site = $this->makeSite( $this->formatPosts() + $files, [ 'supports' => [ 'post-formats' => $formats ] ] );

		$this->boot( $site );

		return $site;
	}

	private function post( string $slug ): \Novaris\Contracts\Content\ContentEntry
	{
		return app( 'content.query' )->make( [ 'path' => '_posts', 'slug' => $slug ] )->single();
	}

	public function testPostsAreStandardWithoutSupport(): void
	{
		foreach ( [ null, false, 'yes' ] as $value ) {
			$this->bootWith( $value );

			$this->assertSame( [], post_formats(), var_export( $value, true ) );
			$this->assertSame( 'standard', $this->post( 'quoted' )->format(), var_export( $value, true ) );
		}
	}

	public function testSupportedFormats(): void
	{
		$this->bootWith( true );
		$this->assertSame( PostFormats::FORMATS, post_formats() );

		// Unknown names are ignored, and names are matched in any case.
		$this->bootWith( [ 'quote', 'LINK', 'banana' ] );
		$this->assertSame( [ 'link', 'quote' ], post_formats() );
		$this->assertTrue( PostFormats::isSupported( 'standard' ) );
		$this->assertFalse( PostFormats::isSupported( 'aside' ) );
	}

	public function testEntriesReportTheirFormat(): void
	{
		$this->bootWith( [ 'quote', 'link' ] );

		$this->assertSame( 'quote', $this->post( 'quoted' )->format() );
		$this->assertTrue( $this->post( 'quoted' )->hasFormat( 'Quote' ) );

		// Front matter is matched in any case.
		$this->assertSame( 'link', $this->post( 'linked' )->format() );

		// Formats the theme doesn't support, unknown formats and posts with
		// no format are standard.
		$this->assertSame( 'standard', $this->post( 'aside' )->format() );
		$this->assertSame( 'standard', $this->post( 'unknown' )->format() );
		$this->assertSame( 'standard', $this->post( 'plain' )->format() );
		$this->assertTrue( $this->post( 'plain' )->hasFormat( 'standard' ) );
	}

	public function testFormatHierarchy(): void
	{
		$this->bootWith( true );

		$this->assertSame( [ 'collection-quote', 'collection' ], $this->post( 'quoted' )->formatHierarchy( 'collection' ) );
		$this->assertSame( [ 'collection' ], $this->post( 'plain' )->formatHierarchy( 'collection' ) );

		$single = Hierarchy::single( $this->post( 'quoted' ) );
		$this->assertSame( [ 'single.quoted', 'single-post-quote', 'single-format-quote', 'single-post', 'single' ], $single );

		$this->assertSame( [ 'single.plain', 'single-post', 'single' ], Hierarchy::single( $this->post( 'plain' ) ) );
	}

	public function testPostClassAddsTheFormat(): void
	{
		$this->bootWith( [ 'quote' ] );

		$this->assertStringContainsString( 'format-quote', post_class( [], $this->post( 'quoted' ) ) );
		$this->assertStringContainsString( 'format-standard', post_class( [], $this->post( 'plain' ) ) );

		// Without an entry, nothing changes.
		$this->assertStringNotContainsString( 'format-', post_class() );
	}

	public function testSinglePostsUseTheirFormatTemplate(): void
	{
		$site = $this->bootWith( [ 'quote' ], [
			'resources/views/index/single-format-quote.php' => '<?php echo "quote template";',
		] );

		$this->assertSame( 'quote template', $this->get( $site, '/blog/quoted' )->getContent() );
		$this->assertStringNotContainsString( 'quote template', $this->get( $site, '/blog/plain' )->getContent() );
	}

	public function testListItemsUseTheirFormatTemplate(): void
	{
		$site = $this->bootWith( [ 'quote', 'link' ], [
			'resources/views/entry/collection.php'       => '<?php echo "[" . $entry->name() . "]";',
			'resources/views/entry/collection-quote.php' => '<?php echo "[quote:" . $entry->name() . "]";',
			'resources/views/index/collection.php'       => '<?php $engine->each( "entry", $entries, "entry", fn( $entry ) => $entry->formatHierarchy( "collection" ) );',
		] );

		$types = $this->contentTypes();
		$types['post']['collection']['number'] = 10;
		$this->write( "{$site}/config/content.php", "<?php\nreturn " . var_export( $types, true ) . ";\n" );

		$html = $this->get( $site, '/blog' )->getContent();

		$this->assertStringContainsString( '[quote:quoted]', $html );

		// `link` has no template of its own, so it falls back.
		$this->assertStringContainsString( '[linked]', $html );
		$this->assertStringContainsString( '[plain]', $html );
	}
}
