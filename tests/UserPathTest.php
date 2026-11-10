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
	private function makeUserFolder( ?string $folder = null ): string
	{
		$folder ??= $this->temporaryDirectory();

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

		@mkdir( "{$folder}/media", 0755, true );
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

	public function testLiveSitesServeMediaFromTheUserPath(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$folder = $this->makeUserFolder();
		$site   = $this->makeSiteUsing( $folder );

		// The web server can't find these under the site's own `user`
		// folder, so the request reaches Novaris, which serves the file.
		$response = $this->get( $site, '/user/media/photo.png' );
		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'image/png', $response->headers->get( 'Content-Type' ) );
		$this->assertSame( "{$folder}/media/photo.png", $response->getFile()->getPathname() );

		// Resized images, written into the folder, are served the same way.
		$this->testPost()->thumbnail( 'tiny' );
		$this->assertSame( 200, $this->get( $site, '/user/media/photo-20x10.png' )->getStatusCode() );

		// Missing files and paths outside the media folder are not served.
		$this->assertSame( 404, $this->get( $site, '/user/media/missing.png' )->getStatusCode() );
		$this->assertSame( 404, $this->get( $site, '/user/media/../content/index.md' )->getStatusCode() );
		$this->assertSame( 404, $this->get( $site, '/user/media/%2E%2E/content/index.md' )->getStatusCode() );
	}

	public function testMediaFilesThatCouldRunScriptsAreSandboxed(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create the test folder.' );
		}

		$folder = $this->makeUserFolder();
		$site   = $this->makeSiteUsing( $folder );

		file_put_contents( "{$folder}/media/icon.svg", '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>' );
		file_put_contents( "{$folder}/media/bare.svg", '<svg><circle r="1"/></svg>' );
		file_put_contents( "{$folder}/media/page.html", '<script>alert(1)</script>' );
		file_put_contents( "{$folder}/media/notes.txt", 'Plain text.' );

		foreach ( [ 'icon.svg', 'bare.svg', 'page.html' ] as $name ) {
			$headers = $this->get( $site, "/user/media/{$name}" )->headers;

			$this->assertStringContainsString( 'sandbox', (string) $headers->get( 'Content-Security-Policy' ), $name );
			$this->assertStringContainsString( "default-src 'none'", (string) $headers->get( 'Content-Security-Policy' ), $name );
			$this->assertSame( 'nosniff', $headers->get( 'X-Content-Type-Options' ), $name );
		}

		// SVGs are always served as SVG, even without an XML declaration.
		$this->assertSame( 'image/svg+xml', $this->get( $site, '/user/media/bare.svg' )->headers->get( 'Content-Type' ) );

		// Other files only get `nosniff`, so PDFs and the like still open
		// in the browser's own viewer.
		$text = $this->get( $site, '/user/media/notes.txt' )->headers;
		$this->assertSame( 'nosniff', $text->get( 'X-Content-Type-Options' ) );
		$this->assertNull( $text->get( 'Content-Security-Policy' ) );
	}

	public function testServeCommandServesPagesAssetsAndMedia(): void
	{
		// Without pcntl, the command runs the server as a child process,
		// which the test can't stop.
		if ( ! extension_loaded( 'gd' ) || ! function_exists( 'pcntl_exec' ) ) {
			$this->markTestSkipped( 'Needs GD and pcntl.' );
		}

		$folder = $this->makeUserFolder();
		$site   = $this->makeSiteUsing( $folder );

		// The command finds the site from its `vendor/autoload.php`.
		$this->write( "{$site}/vendor/autoload.php", "<?php\nreturn require " . var_export( $this->autoloadPath(), true ) . ";\n" );
		$this->write( "{$site}/index.php", "<?php\nrequire 'vendor/autoload.php';\n\$app = new Novaris\\Core\\Application( __DIR__ );\n\$app->boot();\n\$app->make( 'routing.router' )->response()->send();\n" );
		$this->write( "{$site}/public/assets/screen.css", 'body {}' );

		// Find a free port.
		$socket = stream_socket_server( 'tcp://127.0.0.1:0' );
		$port   = (int) substr( strrchr( stream_socket_get_name( $socket, false ), ':' ), 1 );
		fclose( $socket );

		$server = proc_open(
			[ PHP_BINARY, dirname( __DIR__ ) . '/src/bin/novaris', 'serve', '--host=127.0.0.1', "--port={$port}" ],
			[ 1 => [ 'file', '/dev/null', 'w' ], 2 => [ 'file', '/dev/null', 'w' ] ],
			$pipes,
			$site
		);

		$fetch = static function ( string $path ) use ( $port ): array {
			$context = stream_context_create( [ 'http' => [ 'ignore_errors' => true, 'timeout' => 5 ] ] );
			$body    = @file_get_contents( "http://127.0.0.1:{$port}{$path}", false, $context );

			return [ (int) substr( $http_response_header[0] ?? '', 9, 3 ), (string) $body ];
		};

		try {
			// Wait for the server to start.
			for ( $i = 0; $i < 50 && 0 === $fetch( '/public/assets/screen.css' )[0]; $i++ ) {
				usleep( 100000 );
			}

			$this->assertSame( [ 200, 'body {}' ], $fetch( '/public/assets/screen.css' ) );
			$this->assertSame( 200, $fetch( '/user/media/photo.png' )[0] );
			$this->assertSame( file_get_contents( "{$folder}/media/photo.png" ), $fetch( '/user/media/photo.png' )[1] );

			[ $status, $home ] = $fetch( '/' );
			$this->assertSame( 200, $status );
			$this->assertStringContainsString( 'From the test data.', $home );
		} finally {
			proc_terminate( $server );
			proc_close( $server );
		}
	}

	public function testFolderInsideTheSiteIsLinkedAtItsOwnPath(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		// Like `NOVARIS_USER_PATH="theme-test-data"` with the clone inside
		// the site folder.
		$site = $this->makeSiteUsing( '.' );
		$this->makeUserFolder( "{$site}/theme-test-data" );
		$this->write( "{$site}/.env", 'APP_URL="' . static::URL . '"' . PHP_EOL . 'NOVARIS_USER_PATH="theme-test-data"' . PHP_EOL );

		$app = $this->boot( $site );

		$this->assertSame( static::URL . '/theme-test-data/media', $app->mediaUrl() );

		// Links written as `/user/media/...` in content point at the folder's
		// real address, where the web server finds the files by itself.
		$media = static::URL . '/theme-test-data/media';

		$this->assertSame( "{$media}/photo.png", $this->testPost()->thumbnail()?->url() );
		$this->assertSame( "{$media}/photo-20x10.png", $this->testPost()->thumbnail( 'tiny' )?->url() );
		$this->assertStringContainsString( 'src="' . $media . '/photo.png"', $this->testPost()->content() );

		// The export copies the media to the same address.
		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath()
		) )->export();

		$this->assertFileExists( $app->publicPath( 'theme-test-data/media/photo.png' ) );
		$this->assertStringContainsString(
			'/theme-test-data/media/photo.png',
			file_get_contents( $app->publicPath( 'blog/test-post/index.html' ) )
		);
	}

	public function testLinksToUserFilesFollowTheFolder(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$site = $this->makeSiteUsing( '.' );
		$this->makeUserFolder( "{$site}/theme-test-data" );
		$this->write( "{$site}/.env", 'APP_URL="' . static::URL . '"' . PHP_EOL . 'NOVARIS_USER_PATH="theme-test-data"' . PHP_EOL );
		$this->boot( $site );

		$html = app( 'markdown' )->convert( "[![Photo](/user/media/photo.png)](/user/media/photo.png)\n\n[A file](/user/media/photo.png) and [a missing one](/user/media/missing.png)." )->content();

		// The link and the image both point at the folder's real address.
		$this->assertSame( 3, substr_count( $html, static::URL . '/theme-test-data/media/photo.png' ) );

		// Links to missing files keep their address.
		$this->assertStringContainsString( static::URL . '/user/media/missing.png', $html );
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
