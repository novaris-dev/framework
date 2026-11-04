<?php
/**
 * `NOVARIS_USER_PATH` tests.
 *
 * A site can read its content and media from another folder, such as a copy
 * of the theme test data, by setting `NOVARIS_USER_PATH` in `.env`.
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

class UserPathTest extends TestCase
{
	/**
	 * Image sizes used to check that resized images stay in the user folder.
	 */
	private const SIZES = [
		'supports' => [
			'featured-image' => [
				'sizes' => [
					'tiny' => [ 'width' => 20, 'height' => 10, 'crop' => true ],
				],
			],
		],
	];

	protected function setUp(): void
	{
		parent::setUp();

		$this->forgetUserPath();
	}

	protected function tearDown(): void
	{
		// Dotenv doesn't overwrite variables that are already set, so clear
		// this one for the next test.
		$this->forgetUserPath();

		parent::tearDown();
	}

	private function forgetUserPath(): void
	{
		unset( $_ENV['NOVARIS_USER_PATH'], $_SERVER['NOVARIS_USER_PATH'] );
		putenv( 'NOVARIS_USER_PATH' );
	}

	/**
	 * Creates a folder laid out like a site's `user` folder, with a home
	 * page, a post with a thumbnail, and the thumbnail image.
	 */
	private function makeUserFolder(): string
	{
		$folder = $this->temporaryDirectory();

		$files = [
			'content/index.md'                            => $this->markdown( [ 'title' => 'Test data home' ], 'From the test data.' ),
			'content/_posts/index.md'                     => $this->markdown( [ 'title' => 'Blog' ] ),
			'content/_categories/index.md'                => $this->markdown( [ 'title' => 'Categories' ] ),
			'content/_posts/2025-02-01.test-post.md'      => $this->markdown( [
				'title'     => 'Test post',
				'published' => '2025-02-01',
				'thumbnail' => '/user/media/photo.png',
			], "Body.\n\n![Photo](/user/media/photo.png)" ),
		];

		foreach ( $files as $path => $content ) {
			$this->write( "{$folder}/{$path}", $content );
		}

		mkdir( "{$folder}/media" );
		imagepng( imagecreatetruecolor( 40, 30 ), "{$folder}/media/photo.png" );

		return $folder;
	}

	/**
	 * Creates a site whose `.env` sets `NOVARIS_USER_PATH`.
	 */
	private function makeSiteUsing( string $userPath ): string
	{
		return $this->makeSite( [
			'.env' => 'APP_URL="' . static::URL . '"' . PHP_EOL . 'NOVARIS_USER_PATH="' . $userPath . '"' . PHP_EOL,
		], self::SIZES );
	}

	private function testPost(): \Novaris\Contracts\Content\ContentEntry
	{
		return app( 'content.query' )->make( [ 'path' => '_posts', 'slug' => 'test-post' ] )->single();
	}

	public function testSitesUseTheirOwnUserFolderByDefault(): void
	{
		$site = $this->makeSite();
		$app  = $this->boot( $site );

		$this->assertSame( "{$site}/user", $app->userPath() );
		$this->assertSame( "{$site}/user/content", $app->contentPath() );
		$this->assertSame( "{$site}/user/media", $app->mediaPath() );
		$this->assertStringContainsString( 'Welcome home.', $this->get( $site, '/' )->getContent() );
	}

	public function testRelativeUserPathIsReadFromTheEnvFile(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$folder = $this->makeUserFolder();
		$site   = $this->makeSiteUsing( '../' . basename( $folder ) );

		// Both temporary folders are in the same parent folder.
		$this->assertSame( dirname( $site ), dirname( $folder ) );

		$app = $this->boot( $site );

		$this->assertSame( $folder, $app->userPath() );
		$this->assertSame( "{$folder}/content", $app->contentPath() );
		$this->assertSame( "{$folder}/media", $app->mediaPath() );

		// Content comes from the folder, not the site's own `user` folder.
		$home = $this->get( $site, '/' )->getContent();
		$this->assertStringContainsString( 'From the test data.', $home );
		$this->assertStringNotContainsString( 'Welcome home.', $home );

		// Thumbnails are read from the folder, and their URLs stay under
		// `/user`, the same as the links written in the content.
		$thumbnail = $this->testPost()->thumbnail();
		$this->assertSame( "{$folder}/media/photo.png", $thumbnail?->path() );
		$this->assertSame( static::URL . '/user/media/photo.png', $thumbnail->url() );
		$this->assertSame( 40, $thumbnail->width() );

		// Resized images are written next to the original, in the folder.
		$resized = $this->testPost()->thumbnail( 'tiny' );
		$this->assertSame( "{$folder}/media/photo-20x10.png", $resized?->path() );
		$this->assertSame( static::URL . '/user/media/photo-20x10.png', $resized->url() );
		$this->assertDirectoryDoesNotExist( "{$site}/user/media" );
	}

	public function testAbsoluteUserPathWorks(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$folder = $this->makeUserFolder();
		$app    = $this->boot( $this->makeSiteUsing( $folder ) );

		$this->assertSame( "{$folder}/content", $app->contentPath() );
		$this->assertSame( 'Test post', $this->testPost()->title() );
	}

	public function testExportUsesTheUserPath(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$folder = $this->makeUserFolder();
		$app    = $this->boot( $this->makeSiteUsing( $folder ) );

		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath()
		) )->export();

		$this->assertStringContainsString( 'From the test data.', file_get_contents( $app->publicPath( 'index.html' ) ) );
		$this->assertFileExists( $app->publicPath( 'blog/test-post/index.html' ) );

		// Media is copied to `/user/media`, where the content links to it.
		$this->assertFileExists( $app->publicPath( 'user/media/photo.png' ) );
	}

	public function testMissingUserPathStopsWithAClearMessage(): void
	{
		$site = $this->makeSiteUsing( '../this-folder-does-not-exist' );

		[ , $output ] = $this->runPhp(
			'require ' . var_export( $this->autoloadPath(), true ) . ';' . PHP_EOL .
			'new Novaris\Core\Application( ' . var_export( $site, true ) . ' );',
			$site
		);

		$this->assertStringContainsString( 'NOVARIS_USER_PATH is set to "../this-folder-does-not-exist"', strip_tags( html_entity_decode( $output ) ) );
	}

	public function testEmptyUserPathUsesTheSiteUserFolder(): void
	{
		$site = $this->makeSiteUsing( '' );

		$this->assertSame( "{$site}/user", $this->boot( $site )->userPath() );
	}
}
