<?php
/**
 * Application setup, Markdown, image and command-line tests.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

use Novaris\Image\Processor;
use Novaris\Tools\Media;

class SetupTest extends TestCase
{
	/**
	 * Boots a site in a separate process and returns its output. The site
	 * URL check calls `dd()`, which would end the test run.
	 */
	private function bootInProcess( string $site ): string
	{
		[ , $output ] = $this->runPhp(
			'require ' . var_export( $this->autoloadPath(), true ) . ';' . PHP_EOL .
			'$app = new Novaris\Core\Application( ' . var_export( $site, true ) . ' );' . PHP_EOL .
			'echo "booted: ", $app["config"]->get( "app.url" );',
			$site
		);

		return strip_tags( $output );
	}

	public function testMarkdownRendersWithoutAMarkdownConfig(): void
	{
		$html = $this->get( $this->makeSite(), '/about' )->getContent();

		$this->assertStringContainsString( '<main><p>About us.</p>', $html );
	}

	public function testSiteUrlIsRequired(): void
	{
		$site = $this->makeSite( [
			'.env'           => "APP_ENV=local\n",
			'config/app.php' => "<?php\nreturn [ 'url' => env( 'APP_URL' ), 'private' => true ];\n",
		] );

		$this->assertStringContainsString( 'The site URL is not set.', $this->bootInProcess( $site ) );
	}

	public function testSiteUrlCanBeSetInConfigWithoutAppUrl(): void
	{
		$site = $this->makeSite( [
			'.env'           => "APP_ENV=local\n",
			'config/app.php' => "<?php\nreturn [ 'url' => 'https://example.com', 'private' => true ];\n",
		] );

		$this->assertStringContainsString( 'booted: https://example.com', $this->bootInProcess( $site ) );
	}

	public function testThumbnailsAreResized(): void
	{
		if ( ! extension_loaded( 'gd' ) && ! extension_loaded( 'imagick' ) ) {
			$this->markTestSkipped( 'Needs the GD or Imagick extension.' );
		}

		$site = $this->makeSite( [], [
			'supports' => [ 'featured-image' => [ 'sizes' => [ 'thumb' => [ 'width' => 300, 'height' => 200, 'crop' => true ] ] ] ],
		] );

		$this->boot( $site );

		mkdir( "{$site}/user/media", 0755, true );
		$image = imagecreatetruecolor( 800, 600 );
		imagepng( $image, "{$site}/user/media/photo.png" );

		$thumb = ( new Processor() )->process( new Media( '/user/media/photo.png' ), 'thumb' );

		$this->assertSame( [ 300, 200 ], [ $thumb->width(), $thumb->height() ] );
		$this->assertNull( ( new Processor() )->process( new Media( '/user/media/photo.png' ), 'missing-size' ) );
	}

	public function testFeaturedImageIsTurnedOnInConfig(): void
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$files = [
			'user/content/about/index.md'   => $this->markdown( [ 'title' => 'About', 'thumbnail' => '/user/media/photo.png' ] ),
			'user/content/missing/index.md' => $this->markdown( [ 'title' => 'Missing', 'thumbnail' => '/user/media/missing.png' ] ),
		];

		$entry = function ( string $site, string $path ) {
			if ( ! is_file( "{$site}/user/media/photo.png" ) ) {
				mkdir( "{$site}/user/media", 0755, true );
				imagepng( imagecreatetruecolor( 40, 30 ), "{$site}/user/media/photo.png" );
			}

			return $this->boot( $site )->make( 'content.query' )->make( [ 'path' => $path, 'slug' => 'index' ] )->single();
		};

		// Turned on in config/app.php: the entry's thumbnail is returned.
		$site = $this->makeSite( $files, [ 'supports' => [ 'featured-image' => [ 'sizes' => [] ] ] ] );
		$this->assertSame( 40, \Novaris\Theme\Site\featured_image( $entry( $site, 'about' ) )?->width() );

		// A missing image file still returns null instead of crashing.
		$this->assertNull( \Novaris\Theme\Site\featured_image( $entry( $site, 'missing' ) ) );

		// Only in theme.json, not config/app.php: off.
		$site = $this->makeSite( $files + [
			'theme.json' => '{ "name": "Test", "slug": "test", "version": "1.0.0", "supports": { "featured-image": true } }',
		] );
		$this->assertNull( \Novaris\Theme\Site\featured_image( $entry( $site, 'about' ) ) );
	}

	public function testCustomHeaderIsConfiguredInConfig(): void
	{
		// Set in config/app.php: the default header URL is returned.
		$this->boot( $this->makeSite( [], [ 'supports' => [ 'custom-header' => [ 'default' => '/user/media/header.jpg' ] ] ] ) );
		$this->assertSame( '/user/media/header.jpg', \Novaris\Theme\Site\custom_header() );

		// Not configured: an empty string.
		$this->boot( $this->makeSite() );
		$this->assertSame( '', \Novaris\Theme\Site\custom_header() );

		// Only in theme.json, not config/app.php: off.
		$this->boot( $this->makeSite( [
			'theme.json' => '{ "name": "Test", "slug": "test", "version": "1.0.0", "supports": { "custom-header": { "default": "/old.jpg" } } }',
		] ) );
		$this->assertSame( '', \Novaris\Theme\Site\custom_header() );
	}

	public function testMenusHandleLocationsWithoutItems(): void
	{
		$this->boot( $this->makeSite( [], [ 'primary' => [ 'Blog' => '/blog' ] ] ) );

		$primary = \Novaris\Theme\Menu\display( [ 'theme_location' => 'primary', 'echo' => false ] );
		$this->assertStringContainsString( '/blog', $primary );

		$fallback = false;

		\Novaris\Theme\Menu\display( [
			'theme_location' => 'footer',
			'fallback_cb'    => function () use ( &$fallback ) { $fallback = true; },
		] );

		$this->assertTrue( $fallback, 'fallback_cb runs for a location with no items' );
	}

	public function testCliWorksWhenTheFrameworkIsSymlinkedIntoVendor(): void
	{
		$site = $this->makeSite();

		// A site whose vendor/ symlinks to this framework, as a Composer
		// path repository does.
		mkdir( "{$site}/vendor/novaris-dev", 0755, true );
		symlink( dirname( __DIR__ ), "{$site}/vendor/novaris-dev/framework" );
		file_put_contents(
			"{$site}/vendor/autoload.php",
			'<?php require ' . var_export( $this->autoloadPath(), true ) . ';'
		);

		[ $status, $output ] = $this->runCommand(
			[ PHP_BINARY, 'vendor/novaris-dev/framework/src/bin/novaris', '--version' ],
			$site
		);

		$this->assertSame( 0, $status, $output );
		$this->assertStringContainsString( 'Novaris ' . \Novaris\Core\Application::VERSION, $output );
	}

	public function testCliExplainsAMissingAutoloader(): void
	{
		[ $status, $output ] = $this->runCommand(
			[ PHP_BINARY, dirname( __DIR__ ) . '/src/bin/novaris', '--version' ],
			$this->temporaryDirectory()
		);

		$this->assertSame( 1, $status );
		$this->assertStringContainsString( 'unable to find vendor/autoload.php', $output );
	}
}
