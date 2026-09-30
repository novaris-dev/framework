<?php
/**
 * Base test case.
 *
 * Builds small, throwaway Novaris sites in the system temp folder so each
 * test runs against a real, booted application.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

use Novaris\Core\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\{Request, Response};

abstract class TestCase extends BaseTestCase
{
	/**
	 * Site URL used by every test site.
	 */
	protected const URL = 'https://site.test';

	/**
	 * Temporary folders created by the current test.
	 */
	private array $temporary = [];

	/**
	 * Default content types: posts with date archives, a category
	 * taxonomy, a private type, and a type without a sitemap.
	 */
	protected function contentTypes(): array
	{
		return [
			'post' => [
				'path'          => '_posts',
				'collection'    => [ 'order' => 'desc', 'number' => 2 ],
				'date_archives' => true,
				'routing'       => [ 'prefix' => 'blog' ],
			],
			'category' => [
				'path'            => '_categories',
				'taxonomy'        => true,
				'term_collect'    => 'post',
				'term_collection' => [ 'order' => 'desc', 'number' => 2 ],
				'routing'         => [ 'prefix' => 'category' ],
			],
			'secret' => [
				'path'   => '_secret',
				'public' => false,
			],
			'drafts' => [
				'path'    => '_drafts',
				'sitemap' => false,
			],
		];
	}

	/**
	 * Default site content: a home page, an about page, five posts and
	 * one category.
	 */
	protected function defaultContent(): array
	{
		$files = [
			'user/content/index.md'                     => $this->markdown( [ 'title' => 'Home' ], 'Welcome home.' ),
			'user/content/about/index.md'               => $this->markdown( [ 'title' => 'About' ], 'About us.' ),
			'user/content/_posts/index.md'              => $this->markdown( [ 'title' => 'Blog' ] ),
			'user/content/_categories/index.md'         => $this->markdown( [ 'title' => 'Categories' ] ),
			'user/content/_categories/news.md'          => $this->markdown( [ 'title' => 'News' ] ),
			'user/content/_secret/index.md'             => $this->markdown( [ 'title' => 'Secret' ] ),
			'user/content/_secret/one.md'               => $this->markdown( [ 'title' => 'Secret one' ] ),
			'user/content/_drafts/index.md'             => $this->markdown( [ 'title' => 'Drafts' ] ),
			'user/content/_drafts/one.md'               => $this->markdown( [ 'title' => 'Draft one' ] ),
		];

		for ( $i = 1; $i <= 5; $i++ ) {
			$files[ "user/content/_posts/2025-01-0{$i}.post-{$i}.md" ] = $this->markdown( [
				'title'     => "Post {$i}",
				'author'    => 'ben',
				'published' => "2025-01-0{$i}",
				'category'  => [ 'news' ],
			], "Body {$i}" );
		}

		return $files;
	}

	/**
	 * Creates a private test site and returns its path.
	 *
	 * @param array $files   Extra or replacement files, keyed by relative path.
	 *                       A `null` value removes a default file.
	 * @param array $app     Overrides for `config/app.php`.
	 * @param array $cache   Overrides for `config/cache.php`.
	 */
	protected function makeSite( array $files = [], array $app = [], array $cache = [] ): string
	{
		$site = $this->temporaryDirectory();

		$app = array_replace( [
			'title'    => 'Test Site',
			'private'  => true,
			'sitemap'  => true,
			'timezone' => 'UTC',
		], $app );

		$defaults = [
			'.env'                         => 'APP_URL="' . static::URL . '"' . PHP_EOL,
			'theme.json'                   => '{ "name": "Test", "slug": "test", "version": "1.0.0" }',
			'config/app.php'               => $this->configFile( $app, "'url' => env( 'APP_URL' )," ),
			'config/cache.php'             => $this->configFile( $cache ),
			'config/content.php'           => $this->configFile( $this->contentTypes() ),
			'resources/views/index.php'    => $this->view(),
		];

		foreach ( array_replace( $defaults, $this->defaultContent(), $files ) as $path => $content ) {
			if ( null !== $content ) {
				$this->write( "{$site}/{$path}", $content );
			}
		}

		return $site;
	}

	/**
	 * Boots a fresh application for a site.
	 */
	protected function boot( string $site ): Application
	{
		$app = new Application( $site );
		$app->boot();

		return $app;
	}

	/**
	 * Requests a path from a site, using a freshly booted application.
	 */
	protected function get( string $site, string $path ): Response
	{
		return $this->boot( $site )
			->make( 'routing.router' )
			->dispatch( Request::create( $path ) );
	}

	/**
	 * Builds a Markdown file with YAML front matter.
	 */
	protected function markdown( array $meta, string $body = '' ): string
	{
		$yaml = '';

		foreach ( $meta as $key => $value ) {
			$yaml .= $key . ': ' . ( is_array( $value )
				? '[ ' . implode( ', ', $value ) . ' ]'
				: $value ) . "\n";
		}

		return "---\n{$yaml}---\n{$body}\n";
	}

	/**
	 * Writes a file, creating its folder if needed.
	 */
	protected function write( string $path, string $content ): void
	{
		if ( ! is_dir( dirname( $path ) ) ) {
			mkdir( dirname( $path ), 0755, true );
		}

		file_put_contents( $path, $content );
	}

	/**
	 * Runs PHP code in a separate process and returns its exit code and
	 * output. Used for code that ends the process, such as `dd()`.
	 *
	 * @return array{0: int, 1: string}
	 */
	protected function runPhp( string $code, string $cwd ): array
	{
		$script = $this->temporaryDirectory() . '/script.php';

		file_put_contents( $script, "<?php\n" . $code );

		return $this->runCommand( [ PHP_BINARY, $script ], $cwd );
	}

	/**
	 * Runs a command and returns its exit code and combined output.
	 *
	 * @return array{0: int, 1: string}
	 */
	protected function runCommand( array $command, string $cwd ): array
	{
		$process = proc_open(
			$command,
			[ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ],
			$pipes,
			$cwd
		);

		$output = stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );

		fclose( $pipes[1] );
		fclose( $pipes[2] );

		return [ proc_close( $process ), $output ];
	}

	/**
	 * Returns the path to the Composer autoloader used by the tests.
	 */
	protected function autoloadPath(): string
	{
		return dirname( __DIR__ ) . '/vendor/autoload.php';
	}

	/**
	 * Creates an empty temporary folder that is removed after the test.
	 */
	protected function temporaryDirectory(): string
	{
		$path = sys_get_temp_dir() . '/novaris-test-' . bin2hex( random_bytes( 6 ) );

		mkdir( $path, 0755, true );

		return $this->temporary[] = realpath( $path );
	}

	/**
	 * Removes the temporary folders.
	 */
	protected function tearDown(): void
	{
		foreach ( $this->temporary as $path ) {
			$this->removeDirectory( $path );
		}

		$this->temporary = [];

		parent::tearDown();
	}

	/**
	 * Returns a PHP config file that returns the given array.
	 */
	private function configFile( array $values, string $extra = '' ): string
	{
		$export = var_export( $values, true );

		if ( $extra ) {
			$export = preg_replace( '/^array \(/', "array (\n  {$extra}", $export );
		}

		return "<?php\nreturn {$export};\n";
	}

	/**
	 * Minimal template that prints the title, content, entry titles and
	 * pagination, so tests can check what a page contains.
	 */
	private function view(): string
	{
		return <<<'PHP'
<?php
if ( ! empty( $doctitle ) ) {
	echo $doctitle->render();
}

foreach ( [ 'single', 'page', 'home' ] as $name ) {
	if ( ! empty( $$name ) && is_object( $$name ) ) {
		echo '<main>' . $$name->content() . '</main>';
		break;
	}
}

if ( ! empty( $entries ) ) {
	echo '<ul class="entries">';

	foreach ( $entries as $entry ) {
		echo '<li><a href="' . e( $entry->url() ) . '">' . e( $entry->title() ) . '</a></li>';
	}

	echo '</ul>';
}

if ( ! empty( $pagination ) ) {
	$pagination->display();
}
PHP;
	}

	/**
	 * Recursively removes a folder, including symlinks inside it.
	 */
	private function removeDirectory( string $path ): void
	{
		if ( ! file_exists( $path ) && ! is_link( $path ) ) {
			return;
		}

		if ( is_link( $path ) || is_file( $path ) ) {
			unlink( $path );
			return;
		}

		foreach ( scandir( $path ) as $item ) {
			if ( '.' !== $item && '..' !== $item ) {
				$this->removeDirectory( "{$path}/{$item}" );
			}
		}

		rmdir( $path );
	}
}
