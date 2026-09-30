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
		$this->assertStringContainsString( 'Novaris 1.0.0', $output );
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
