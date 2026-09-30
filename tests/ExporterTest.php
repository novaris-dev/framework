<?php
/**
 * Static export tests.
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
use ReflectionClass;

class ExporterTest extends TestCase
{
	/**
	 * Exports a site and returns the export folder.
	 */
	private function export( string $site, ?string $url = null ): string
	{
		$app = $this->boot( $site );

		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath(),
			$url
		) )->export();

		return $app->publicPath();
	}

	/**
	 * Calls the exporter's URL rewriting on a string of HTML.
	 */
	private function rewrite( string $site, string $html, ?string $url = null ): string
	{
		$this->boot( $site );

		$class    = new ReflectionClass( Exporter::class );
		$exporter = $class->newInstanceWithoutConstructor();

		$property = $class->getProperty( 'url' );
		$property->setValue( $exporter, $url );

		$method = $class->getMethod( 'rewriteUrls' );

		return $method->invoke( $exporter, $html );
	}

	public function testExportsEveryPageWithoutBrokenLinks(): void
	{
		$public = $this->export( $this->makeSite(), 'https://example.org' );

		$missing = [];

		foreach ( glob( "{$public}/{,*/,*/*/,*/*/*/,*/*/*/*/,*/*/*/*/*/}index.html", GLOB_BRACE ) as $file ) {
			preg_match_all( '#href="https://example\.org([^"\#?]*)"#', file_get_contents( $file ), $links );

			foreach ( $links[1] as $link ) {
				if ( ! is_file( $public . rtrim( $link, '/' ) . '/index.html' ) ) {
					$missing[] = "{$link} (linked from " . substr( $file, strlen( $public ) ) . ')';
				}
			}
		}

		$this->assertSame( [], array_values( array_unique( $missing ) ) );
	}

	public function testExportsEveryPageOfPaginatedArchives(): void
	{
		$public = $this->export( $this->makeSite() );

		// Five posts at two per page means three pages for each listing.
		foreach ( [ 'blog', 'blog/2025', 'blog/2025/01', 'category/news', 'author/ben' ] as $listing ) {
			$this->assertFileExists( "{$public}/{$listing}/page/2/index.html", "{$listing} page 2" );
			$this->assertFileExists( "{$public}/{$listing}/page/3/index.html", "{$listing} page 3" );
			$this->assertFileDoesNotExist( "{$public}/{$listing}/page/4/index.html", "{$listing} page 4" );
		}
	}

	public function testExportsEachContentTypesOwnEntries(): void
	{
		$public = $this->export( $this->makeSite() );

		$this->assertFileExists( "{$public}/blog/post-1/index.html" );
		$this->assertFileExists( "{$public}/category/news/index.html" );
	}

	public function testOnlyRemovesFilesFromThePreviousExport(): void
	{
		$site = $this->makeSite( [ 'public/google-verification.html' => 'keep me' ] );

		$this->export( $site );
		$public = $this->export( $site );

		$this->assertFileExists( "{$public}/google-verification.html" );
		$this->assertFileExists( "{$public}/index.html" );
	}

	public function testRewritesOnlyThisSitesAssetUrls(): void
	{
		$site = $this->makeSite();

		$cases = [
			// Input => expected output.
			'<link href="https://site.test/themes/nova/public/assets/app.css">'
				=> '<link href="/assets/app.css">',
			'<script src="https://site.test/public/assets/app.js"></script>'
				=> '<script src="/assets/app.js"></script>',
			'<a href="/about">About</a> <img src="/public/assets/logo.png">'
				=> '<a href="/about">About</a> <img src="/assets/logo.png">',
			"<img src='https://site.test/public/assets/a.png'>"
				=> "<img src='/assets/a.png'>",
			'background:url(https://site.test/public/assets/bg.png)'
				=> 'background:url(/assets/bg.png)',
			'<img srcset="https://site.test/public/assets/a.png 1x,https://site.test/public/assets/b.png 2x">'
				=> '<img srcset="/assets/a.png 1x,/assets/b.png 2x">',
			'<script src="https://cdn.example.com/lib/public/assets/x.js"></script>'
				=> '<script src="https://cdn.example.com/lib/public/assets/x.js"></script>',
			'<p>See docs/public/assets/ folder</p>'
				=> '<p>See docs/public/assets/ folder</p>',
		];

		foreach ( $cases as $input => $expected ) {
			$this->assertSame( $expected, $this->rewrite( $site, $input ), $input );
		}
	}

	public function testRewritesAssetUrlsForSitesInASubfolder(): void
	{
		$site = $this->makeSite( [], [ 'url' => 'https://site.test/blog' ] );

		$this->assertSame(
			'<img src="/assets/a.png"><img src="/assets/b.png">',
			$this->rewrite( $site, '<img src="https://site.test/blog/public/assets/a.png"><img src="/blog/themes/nova/public/assets/b.png">' )
		);
	}

	public function testRewritesSiteUrlsForTheExportUrl(): void
	{
		$this->assertSame(
			'<a href="https://example.org/about">x</a><img src="https://example.org/assets/a.png">',
			$this->rewrite(
				$this->makeSite(),
				'<a href="https://site.test/about">x</a><img src="https://site.test/public/assets/a.png">',
				'https://example.org'
			)
		);
	}
}
