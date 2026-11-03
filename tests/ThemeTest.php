<?php
/**
 * Theme name, install and loading tests.
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
use Novaris\Theme\{Installer, Metadata, Name};
use ZipArchive;

class ThemeTest extends TestCase
{
	/**
	 * Returns a theme config file that sets the site title, so tests can
	 * see which theme's config was loaded.
	 */
	private function themeConfig( string $title ): string
	{
		return "<?php\nreturn [ 'title' => " . var_export( $title, true ) . " ];\n";
	}

	public function testPlainNamesUseTheDefaultOwner(): void
	{
		$this->assertSame( 'amicable', Name::slug( 'amicable' ) );
		$this->assertSame( 'novaris-dev/amicable', Name::repository( 'amicable' ) );
	}

	public function testOwnerNamesUseTheirOwnRepository(): void
	{
		$this->assertSame( 'their-theme', Name::slug( 'someone/their-theme' ) );
		$this->assertSame( 'someone/their-theme', Name::repository( 'someone/their-theme' ) );

		$this->assertSame( 'amicable', Name::slug( 'novaris-dev/amicable' ) );
		$this->assertSame( 'novaris-dev/amicable', Name::repository( 'novaris-dev/amicable' ) );
	}

	public function testRejectsUnsafeOrMalformedNames(): void
	{
		foreach ( [ '', '../x', 'x/..', 'a/../b', 'a/b/c', '/x', 'x/', '.hidden', '-owner/x', 'owner-/x', 'own--er/x', 'a b', 'x\\y' ] as $name ) {
			$this->assertFalse( Name::isValid( $name ), var_export( $name, true ) );
		}

		foreach ( [ 'amicable', 'theme_2.0', 'Org-Name/my.theme', 'user123/x' ] as $name ) {
			$this->assertTrue( Name::isValid( $name ), $name );
		}

		$this->expectException( \InvalidArgumentException::class );
		Name::slug( '../x' );
	}

	public function testInstallerDownloadsFromTheRightRepository(): void
	{
		if ( ! class_exists( ZipArchive::class ) ) {
			$this->markTestSkipped( 'Needs the PHP zip extension.' );
		}

		$themes = $this->temporaryDirectory();

		// Instead of GitHub, build a release zip locally and record which
		// repository and slug were asked for.
		$installer = new class( new Metadata() ) extends Installer {
			public array $downloads = [];

			public function download( string $theme, string $repository, string $destination ): string
			{
				$this->downloads[] = [ $repository, $theme ];

				$file = "{$destination}/{$theme}.1.0.0.zip";
				$zip  = new ZipArchive();
				$zip->open( $file, ZipArchive::CREATE );
				$zip->addFromString( "{$theme}/theme.json", json_encode( [ 'name' => $theme, 'slug' => $theme, 'version' => '1.0.0' ] ) );
				$zip->close();

				return $file;
			}
		};

		$this->assertSame( "{$themes}/amicable", $installer->install( 'amicable', $themes ) );
		$this->assertSame( "{$themes}/their-theme", $installer->install( 'someone/their-theme', $themes ) );

		$this->assertSame( [
			[ 'novaris-dev/amicable', 'amicable' ],
			[ 'someone/their-theme', 'their-theme' ],
		], $installer->downloads );

		$this->assertFileExists( "{$themes}/their-theme/theme.json" );
	}

	public function testSitesLoadOwnerNamedThemesAndParents(): void
	{
		$site = $this->makeSite( [
			'themes/their-theme/theme.json'      => '{ "name": "Child", "slug": "their-theme", "version": "1.0.0", "parent": "someone/base" }',
			'themes/their-theme/config/app.php'  => $this->themeConfig( 'From the child theme' ),
			'themes/base/theme.json'             => '{ "name": "Base", "slug": "base", "version": "1.0.0" }',
			'themes/base/config/app.php'         => "<?php\nreturn [ 'title' => 'From the base theme', 'tagline' => 'Base tagline' ];\n",
			'themes/base/public/assets/manifest.json' => '{ "resources/js/app.js": { "file": "app-123.js" } }',
		], [
			'private' => false,
			'theme'   => 'someone/their-theme',
		] );

		// The site's own config/app.php sets a title, so drop it to see the
		// theme defaults come through.
		$this->write( "{$site}/config/app.php", "<?php\nreturn [ 'url' => env( 'APP_URL' ), 'theme' => 'someone/their-theme', 'sitemap' => true, 'timezone' => 'UTC' ];\n" );

		$app = $this->boot( $site );

		$this->assertSame( "{$site}/themes/their-theme", $app->themePath() );
		$this->assertSame( "{$site}/themes/base", $app->parentThemePath() );

		// The child theme's config wins, with the parent's as defaults.
		$this->assertSame( 'From the child theme', $app['config']->get( 'app.title' ) );
		$this->assertSame( 'Base tagline', $app['config']->get( 'app.tagline' ) );

		// Asset URLs use the parent theme's folder, not `owner/slug`.
		$this->assertSame( static::URL . '/themes/base/public/assets/app-123.js', asset( 'resources/js/app.js' ) );
	}

	public function testAssetUrlsUseTheThemeFolder(): void
	{
		$site = $this->makeSite( [
			'themes/solo/theme.json'                  => '{ "name": "Solo", "slug": "solo", "version": "1.0.0" }',
			'themes/solo/public/assets/manifest.json' => '{ "resources/js/app.js": { "file": "app-123.js" } }',
		], [
			'private' => false,
			'theme'   => 'someone/solo',
		] );

		$this->boot( $site );

		$this->assertSame( static::URL . '/themes/solo/public/assets/app-123.js', asset( 'resources/js/app.js' ) );
	}

	public function testExportCopiesAssetsFromTheThemeFolder(): void
	{
		$site = $this->makeSite( [
			'themes/solo/theme.json'                  => '{ "name": "Solo", "slug": "solo", "version": "1.0.0" }',
			'themes/solo/public/assets/app-123.js'    => 'console.log( "solo" );',
		], [
			'private' => false,
			'theme'   => 'someone/solo',
		] );

		$app = $this->boot( $site );

		( new Exporter(
			$app->make( RoutingRouter::class ),
			$app->make( ContentTypes::class ),
			$app->make( ContentQuery::class ),
			$app->publicPath()
		) )->export();

		$this->assertFileExists( $app->publicPath( 'assets/app-123.js' ) );
	}

	public function testInvalidThemeNamesStopWithAClearMessage(): void
	{
		$site = $this->makeSite( [], [ 'private' => false, 'theme' => '../../etc' ] );

		[ , $output ] = $this->runPhp(
			'require ' . var_export( $this->autoloadPath(), true ) . ';' . PHP_EOL .
			'new Novaris\Core\Application( ' . var_export( $site, true ) . ' );',
			$site
		);

		$this->assertStringContainsString( 'Invalid theme name', strip_tags( $output ) );
		$this->assertDirectoryDoesNotExist( dirname( $site ) . '/etc' );
	}
}
