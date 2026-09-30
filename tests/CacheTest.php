<?php
/**
 * Page cache, purge URL, cache driver and directory cache tests.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

use Novaris\Cache\Drivers\File;
use Novaris\Directory\Repository;

class CacheTest extends TestCase
{
	/**
	 * Creates a site with the full-page cache turned on.
	 */
	private function cachedSite( array $files = [] ): string
	{
		return $this->makeSite( $files, [], [
			'global'    => true,
			'expires'   => 0,
			'purge_key' => 'secret123',
		] );
	}

	public function testPageCacheServesCachedPages(): void
	{
		$site = $this->cachedSite();

		$this->get( $site, '/about' );

		$this->assertFileExists( "{$site}/storage/cache/global/about.cache" );
		$this->assertStringContainsString( 'About us.', $this->get( $site, '/about' )->getContent() );
	}

	public function testPageCacheShowsEditsImmediately(): void
	{
		$site = $this->cachedSite();
		$file = "{$site}/user/content/about/index.md";

		touch( $file, time() - 60 );
		touch( dirname( $file ), time() - 60 );

		$this->assertStringContainsString( 'About us.', $this->get( $site, '/about' )->getContent() );

		// Edit in the same second the page was cached.
		file_put_contents( $file, $this->markdown( [ 'title' => 'About' ], 'Edited text.' ) );

		$this->assertStringContainsString( 'Edited text.', $this->get( $site, '/about' )->getContent() );
	}

	public function testPageCacheExcludesFeedsButNotPagesStartingWithFeed(): void
	{
		$site = $this->cachedSite( [
			'user/content/feedback/index.md' => $this->markdown( [ 'title' => 'Feedback' ], 'Tell us.' ),
		] );

		$this->get( $site, '/feedback' );

		$this->assertFileExists( "{$site}/storage/cache/global/feedback.cache" );
	}

	public function testPurgeUrlFlushesTheCache(): void
	{
		$site = $this->cachedSite();

		$this->get( $site, '/about' );
		$this->assertFileExists( "{$site}/storage/cache/global/about.cache" );

		$wrong = $this->get( $site, '/purge/cache/wrong-key' );
		$this->assertStringContainsString( 'Cache Flush Failure', $wrong->getContent() );
		$this->assertFileExists( "{$site}/storage/cache/global/about.cache" );

		$right = $this->get( $site, '/purge/cache/secret123' );
		$this->assertStringContainsString( 'Cache Stores Flushed', $right->getContent() );
		$this->assertFileDoesNotExist( "{$site}/storage/cache/global/about.cache" );

		$store = $this->get( $site, '/purge/cache/content/secret123' );
		$this->assertStringContainsString( 'Cache Store Flushed', $store->getContent() );
	}

	public function testCorruptCacheFilesAreTreatedAsMissing(): void
	{
		$path  = $this->temporaryDirectory();
		$store = ( new File( 'test', [ 'path' => $path ] ) )->make();

		file_put_contents( "{$path}/broken.cache", 'not serialized data' );

		$this->assertNull( $store->get( 'broken' ) );
		$this->assertFileDoesNotExist( "{$path}/broken.cache" );
	}

	public function testDirectoryLookupsAreCached(): void
	{
		$this->boot( $this->makeSite() );

		$repository = new class extends Repository {
			public int $calls = 0;
			public bool $down = false;

			protected function fetch( string $slug ): array
			{
				$this->calls++;

				return $this->down
					? [ 'cp_themes_api' => [ 'error' => 'down' ], 'wp_plugins_api' => [ 'error' => 'down' ] ]
					: [ 'cp_themes_api' => [ [ 'name' => 'Theme' ] ] ];
			}
		};

		$repository->get( 'twentytwenty' );
		$repository->get( 'twentytwenty' );
		$this->assertSame( 1, $repository->calls );
		$this->assertEqualsWithDelta( DAY_IN_SECONDS, \Novaris\Cache::expires( 'directory.twentytwenty' ) - time(), 5 );

		$repository->down = true;
		$repository->get( 'petite' );
		$this->assertEqualsWithDelta( 5 * MINUTE_IN_SECONDS, \Novaris\Cache::expires( 'directory.petite' ) - time(), 5 );
	}
}
