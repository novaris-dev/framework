<?php
/**
 * Content filesystem locator.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Content\Locator;

// Contracts.
use Novaris\Contracts\Content\ContentLocator;

// Classes.
use Novaris\Core\Proxies\{App, Cache, Config};
use Novaris\Tools\Str;
use Symfony\Component\Yaml\Yaml;

class File implements ContentLocator
{
	/**
	 * Full path to the user content folder to search.
	 *
	 * @since 1.0.0
	 */
	protected string $path;

	/**
	 * Full path to the active theme content folder to search.
	 *
	 * @since 1.0.0
	 */
	protected string $theme_path;

	/**
	 * File extension for the store's files without the preceding dot.
	 *
	 * @since  1.0.0
	 */
	protected string $extension = 'md';

	/**
	 * Array of located filepaths and metadata.
	 *
	 * @since 1.0.0
	 */
	protected ?array $located = null;

	/**
	 * Relative path to the cache folder where to store located content.
	 *
	 * @since 1.0.0
	 */
	protected string $cache_key = '';

	/**
	 * Stores the newest result of `filemtime()` for the content folders.
	 *
	 * @since 1.0.0
	 */
	protected int|false|null $content_time = null;

	/**
	 * Stores the result of `filemtime()` on the cache file.
	 *
	 * @since 1.0.0
	 */
	protected ?int $cache_time = null;

	/**
	 * Sets up object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $path = '' )
	{
		$this->path       = App::resolve( 'path.content' );
		$this->theme_path = theme_path( 'user/content' );

		if ( $path ) {
			$this->setPath( $path );
		}
	}

	/**
	 * Sets the locator path. The path is relative to the user and theme
	 * content folders. If no value is passed in, it will be the root.
	 *
	 * @since 1.0.0
	 */
	public function setPath( string $path ): void
	{
		// Remove slashes and dots from the left/right sides.
		$path = trim( $path, '/.' );

		if ( $path ) {
			$this->path       = Str::appendPath( $this->path, $path );
			$this->theme_path = Str::appendPath( $this->theme_path, $path );
		}

		// Replace slash with dot for cache key.
		$this->cache_key = str_replace( '/', '.', $path ?: 'index' );
	}

	/**
	 * Returns the folder path relative to the content directory.
	 *
	 * @since 1.0.0
	 */
	public function path(): string
	{
		return $this->path;
	}

	/**
	 * Returns the file extension for the entries without the preceding dot.
	 *
	 * @since  1.0.0
	 */
	protected function extension(): string
	{
		return trim( $this->extension, '.' );
	}

	/**
	 * Returns collection of located files as an array. The filenames are
	 * the array keys and the metadata is the value.
	 *
	 * @since 1.0.0
	 */
	public function all(): array
	{
		// If the content has already been located, return it early.
		if ( ! is_null( $this->located ) ) {
			return $this->located;
		}

		// Get cached entries if any exist. If not, locate entries from
		// the filesystem.
		if ( ! $entries = $this->getCachedEntries() ) {

			// Cache the located entries.
			if ( $entries = $this->locate() ) {
				Cache::put(
					"content.{$this->cache_key}",
					$entries,
					Config::get( 'cache.expires' )
				);
			}
		}

		// Set or reset located array (it should be `null` here).
		$this->located = [];

		// Loop through the entries and re-add the full filepath as the
		// key. User content takes precedence over theme content.
		foreach ( $entries as $basename => $data ) {
			$user_filepath = Str::appendPath( $this->path, $basename );

			$filepath = file_exists( $user_filepath )
				? $user_filepath
				: Str::appendPath( $this->theme_path, $basename );

			$this->located[ $filepath ] = $data;
		}

		// Return located entries.
		return $this->located;
	}

	/**
	 * Returns the cached filenames and metadata.
	 *
	 * @since 1.0.0
	 */
	protected function getCachedEntries(): array
	{
		$store = Cache::store( 'content' );

		// Check cache for entries. If none found, return empty array.
		if ( ! $cache = $store->get( $this->cache_key ) ) {
			return [];
		}

		// If we've already checked file modified times, return the cache.
		if ( ! is_null( $this->content_time ) && ! is_null( $this->cache_time ) ) {
			return $cache;
		}

		// Get the newest modified time from either the user content
		// folder or the active theme content folder.
		$user_time = is_dir( $this->path )
			? filemtime( $this->path )
			: false;

		$theme_time = is_dir( $this->theme_path )
			? filemtime( $this->theme_path )
			: false;

		$times = array_filter(
			[ $user_time, $theme_time ],
			fn( $time ) => false !== $time
		);

		$this->content_time = $times ? max( $times ) : false;
		$this->cache_time   = $store->created( $this->cache_key );

		// If there are no modified file times or the content folder is
		// newer than the cache, forget the current cache.
		if (
			false === $this->cache_time ||
			false === $this->content_time ||
			$this->content_time > $this->cache_time
		) {
			$store->forget( $this->cache_key );
			$cache = [];
		}

		// Return the cache, empty or otherwise.
		return $cache;
	}

	/**
	 * Locates content files and returns them as an array with the filename
	 * as the key and the metadata as the value.
	 *
	 * Theme content is loaded first so matching user content can override it.
	 *
	 * @since 1.0.0
	 */
	protected function locate(): array
	{
		$located = [];

		// Locate theme content first.
		if ( is_dir( $this->theme_path ) ) {
			$located = $this->locatePath( $this->theme_path );
		}

		// Locate user content second so matching files override theme files.
		if ( is_dir( $this->path ) ) {
			$located = array_replace(
				$located,
				$this->locatePath( $this->path )
			);
		}

		return $located;
	}

	/**
	 * Locates content files within a specific content folder.
	 *
	 * @since 1.0.0
	 */
	protected function locatePath( string $path ): array
	{
		$search = Str::appendPath(
			$path,
			sprintf( '*.%s', $this->extension() )
		);

		// Return an empty array if search results in no files.
		if ( ! $filepaths = glob( $search ) ) {
			return [];
		}

		// Set up new array for located entries.
		$located = [];

		// Get the metadata keys to exclude from the cache.
		$exclude = Config::get( 'cache.content_exclude_meta' );
		$exclude = is_array( $exclude ) ? array_flip( $exclude ) : [];

		// Don't allow exclusion of visibility.
		unset( $exclude['visibility'] );

		// Loop through filepaths and add them to the located array
		// unless they should be excluded.
		foreach ( $filepaths as $filepath ) {

			// Set up empty array in case there's no data.
			$data = [];

			// Get the first 4 kb of data from the file. We're only
			// grabbing the frontmatter. Anything even encroaching
			// this number would be insane.
			$content = file_get_contents(
				$filepath, false, null, 0, 4 * 1024
			);

			if ( $content ) {
				$data = Str::frontMatter( $content );

				// Exclude meta from cache.
				if ( $exclude ) {
					$data = array_diff_key( $data, $exclude );
				}

				// If visibility isn't set, add it now.
				if ( ! isset( $data['visibility'] ) ) {
					$data['visibility'] = Str::startsWith(
						pathinfo( $filepath, PATHINFO_FILENAME ),
						'_'
					) ? 'hidden' : 'public';
				}

				// Update old `date` key to `published`.
				if ( isset( $data['date'] ) && ! isset( $data['published'] ) ) {
					$data['published'] = $data['date'];
					unset( $data['date'] );
				}
			}

			// Create the array key with just the file basename.
			$key = Str::trimSlashes(
				str_replace( $path, '', $filepath )
			);

			$located[ $key ] = $data;
		}

		return $located;
	}
}