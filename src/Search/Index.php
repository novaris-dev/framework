<?php
/**
 * Search index.
 *
 * Builds the list of public content that `/search.json` returns, for themes
 * to search in the browser. It works the same on live sites and static
 * exports, because the export writes the same JSON to `search.json`.
 *
 * Search is turned on in config/app.php with `supports.search`: `true`, or an
 * array of options. The only option is `length`, the most characters of each
 * entry's text to include (default 5000).
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Search;

use Novaris\Content\Pages;
use Novaris\Contracts\Content\{ContentEntry, ContentTypes};
use Novaris\Core\Proxies\{Config, Query};

class Index
{
	/**
	 * Default for the most characters of text to include per entry.
	 *
	 * @since 1.1.2
	 */
	public const LENGTH = 5000;

	/**
	 * Sets up the object state.
	 *
	 * @since 1.1.2
	 */
	public function __construct( protected ContentTypes $types ) {}

	/**
	 * Whether search is turned on: `supports.search` is `true` or an array
	 * of options.
	 *
	 * @since 1.1.2
	 */
	public static function enabled(): bool
	{
		$support = static::support();

		return true === $support || is_array( $support );
	}

	/**
	 * Returns the most characters of each entry's text to include.
	 *
	 * @since 1.1.2
	 */
	public static function length(): int
	{
		$support = static::support();
		$length  = is_array( $support ) ? ( $support['length'] ?? null ) : null;

		return is_int( $length ) && $length >= 0 ? $length : static::LENGTH;
	}

	/**
	 * Returns the `supports.search` setting.
	 *
	 * @since 1.1.2
	 */
	protected static function support(): mixed
	{
		$key = 'app.supports.search';

		return Config::exists( $key ) ? Config::get( $key ) : false;
	}

	/**
	 * Returns the index: one record per public entry.
	 *
	 * @since 1.1.2
	 * @return array<int, array<string, mixed>>
	 */
	public function entries(): array
	{
		$records = [];

		foreach ( $this->types->all() as $type ) {

			if ( ! $type->hasSearch() ) {
				continue;
			}

			// Pages are found by walking the content folder, as the
			// export does, since they have no routes of their own.
			$entries = 'page' === $type->name()
				? Pages::all()
				: Query::make( [
					'type'    => $type->name(),
					'number'  => 0,
					'orderby' => 'published',
					'order'   => 'desc',
				] );

			foreach ( $entries as $entry ) {
				if ( $entry->isPublic() && $entry->url() ) {
					$records[] = $this->record( $entry );
				}
			}
		}

		return $records;
	}

	/**
	 * Returns the index as JSON.
	 *
	 * @since 1.1.2
	 */
	public function json(): string
	{
		return (string) json_encode(
			[ 'entries' => $this->entries() ],
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
		);
	}

	/**
	 * Returns the record for an entry.
	 *
	 * @since 1.1.2
	 * @return array<string, mixed>
	 */
	protected function record( ContentEntry $entry ): array
	{
		$terms = [];

		foreach ( $entry->taxonomies() as $name => $taxonomy ) {

			if ( ! $taxonomy->isPublic() || ! ( $query = $entry->terms( $name ) ) ) {
				continue;
			}

			foreach ( $query as $term ) {
				$terms[ $name ][] = $term->title();
			}
		}

		$text = $this->plain( $entry->content() );

		if ( mb_strlen( $text ) > static::length() ) {
			$text = rtrim( mb_substr( $text, 0, static::length() ) );
		}

		return [
			'title'   => $entry->title(),
			'url'     => $entry->url(),
			'type'    => $entry->type()->name(),
			'date'    => $entry->published( 'Y-m-d' ),
			'terms'   => $terms ?: (object) [],
			'excerpt' => $this->plain( $entry->excerpt( 30 ) ),
			'text'    => $text,
		];
	}

	/**
	 * Turns HTML into plain text on one line.
	 *
	 * @since 1.1.2
	 */
	protected function plain( string $html ): string
	{
		// Keep words in separate elements apart, such as table cells.
		$html = preg_replace( '/<(br|\/p|\/li|\/h[1-6]|\/td|\/th|\/div|\/figcaption|\/pre|\/blockquote)\b[^>]*>/i', '$0 ', $html );
		$text = html_entity_decode( strip_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}
}
