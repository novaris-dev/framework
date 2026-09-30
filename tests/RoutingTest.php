<?php
/**
 * Routing and controller tests.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

class RoutingTest extends TestCase
{
	/**
	 * Asserts the status code for each path.
	 */
	private function assertStatuses( string $site, array $expected ): void
	{
		$actual = [];

		foreach ( array_keys( $expected ) as $path ) {
			$actual[ $path ] = $this->get( $site, $path )->getStatusCode();
		}

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Returns the pagination links on a page, relative to the site URL.
	 */
	private function paginationLinks( string $site, string $path ): array
	{
		$html = $this->get( $site, $path )->getContent();

		preg_match( '#<nav.*?</nav>#s', $html, $nav );
		preg_match_all( '#href="' . preg_quote( static::URL, '#' ) . '([^"]*)"#', $nav[0] ?? '', $links );

		return array_values( array_unique( $links[1] ) );
	}

	public function testPagesRender(): void
	{
		$this->assertStatuses( $this->makeSite(), [
			'/'                   => 200,
			'/about'              => 200,
			'/blog'               => 200,
			'/blog/post-1'        => 200,
			'/blog/2025'          => 200,
			'/blog/2025/01'       => 200,
			'/category/news'      => 200,
			'/author/ben'         => 200,
			'/does-not-exist'     => 404,
			'/blog/2024/13'       => 404,
		] );
	}

	public function testPageZeroIsNotFound(): void
	{
		$this->assertStatuses( $this->makeSite(), [
			'/blog/page/0'            => 404,
			'/blog/2025/page/0'       => 404,
			'/category/news/page/0'   => 404,
			'/author/ben/page/0'      => 404,
		] );
	}

	public function testHugePageNumbersAreNotFound(): void
	{
		$this->assertStatuses( $this->makeSite(), [
			'/blog/page/9223372036854775807'             => 404,
			'/blog/2025/page/99999999999999999999'       => 404,
			'/category/news/page/99999999999999999999'   => 404,
			'/author/ben/page/99999999999999999999'      => 404,
		] );
	}

	public function testPaginationLinksPointToTheSameListing(): void
	{
		$site = $this->makeSite();

		$this->assertSame(
			[ '/blog', '/blog/page/3' ],
			$this->paginationLinks( $site, '/blog/page/2' )
		);

		$this->assertSame(
			[ '/category/news', '/category/news/page/3' ],
			$this->paginationLinks( $site, '/category/news/page/2' )
		);
	}

	public function testSitemapsRespectTypeSettings(): void
	{
		$this->assertStatuses( $this->makeSite(), [
			'/sitemap/post'    => 200,
			'/sitemap/secret'  => 404, // public: false
			'/sitemap/drafts'  => 404, // sitemap: false
			'/sitemap/bogus'   => 404, // not a content type
		] );
	}

	public function testSitemapIndexListsOnlyTypesWithSitemaps(): void
	{
		$types = $this->boot( $this->makeSite() )->make( 'content.types' );

		$this->assertTrue( $types->get( 'post' )->hasSitemap() );
		$this->assertFalse( $types->get( 'secret' )->hasSitemap() );
		$this->assertFalse( $types->get( 'drafts' )->hasSitemap() );
	}

	public function testDotDotPathsCannotLeaveTheContentFolder(): void
	{
		$site = $this->makeSite( [
			'secret/notes.md' => $this->markdown( [ 'title' => 'Private notes' ], 'API_KEY=hunter2' ),
		] );

		$response = $this->get( $site, '/about/../../../secret/notes' );

		$this->assertSame( 404, $response->getStatusCode() );
		$this->assertStringNotContainsString( 'hunter2', $response->getContent() );
	}

	public function testMissingIndexShowsANoticeAsA404(): void
	{
		$response = $this->get( $this->makeSite( [ 'user/content/index.md' => null ] ), '/' );

		$this->assertSame( 404, $response->getStatusCode() );
		$this->assertStringContainsString( 'user/content/index.md', $response->getContent() );
		$this->assertStringNotContainsString( sys_get_temp_dir(), $response->getContent() );
	}

	public function testUrlPayloadsAreNotReflectedUnescaped(): void
	{
		$site    = $this->makeSite();
		$payload = '"><script>alert(1)</script>';

		foreach ( [ '/', '/blog/', '/category/', '/author/', '/blog/2025/' ] as $prefix ) {
			$html = $this->get( $site, $prefix . rawurlencode( $payload ) )->getContent();

			$this->assertStringNotContainsString( '<script>alert(1)</script>', $html, $prefix );
		}
	}
}
