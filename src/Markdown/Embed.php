<?php
/**
 * Embeds.
 *
 * Turns a link on its own line in Markdown into an embed: a YouTube or Vimeo
 * video, or a player for a video or audio file. Nothing is fetched from the
 * internet while building the page; the embed is worked out from the link,
 * so it works the same on live sites and static exports.
 *
 * YouTube and Vimeo use "click to load" by default: the page shows a play
 * button (and, for YouTube, the video's thumbnail), and the theme's script
 * swaps in the real player when it's clicked, so the provider's scripts only
 * load for visitors who play the video. Without the script, the play button
 * is a normal link to the video.
 *
 * Embeds are turned on in config/app.php with `supports.embeds`: `true`, or
 * an array of options:
 *
 * - `click_to_load` (default `true`): `false` puts the provider's player in
 *   the page straight away, as a lazy-loaded iframe.
 * - `privacy` (default `true`): YouTube players use youtube-nocookie.com and
 *   Vimeo players use `dnt=1`.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Markdown;

use Novaris\Core\Proxies\Config;
use Novaris\Tools\Media;

class Embed
{
	/**
	 * Video file extensions, with their MIME types.
	 *
	 * @since 1.1.3
	 */
	public const VIDEO = [
		'mp4'  => 'video/mp4',
		'm4v'  => 'video/mp4',
		'webm' => 'video/webm',
		'ogv'  => 'video/ogg',
		'mov'  => 'video/quicktime',
	];

	/**
	 * Audio file extensions, with their MIME types.
	 *
	 * @since 1.1.3
	 */
	public const AUDIO = [
		'mp3'  => 'audio/mpeg',
		'm4a'  => 'audio/mp4',
		'ogg'  => 'audio/ogg',
		'oga'  => 'audio/ogg',
		'wav'  => 'audio/wav',
		'flac' => 'audio/flac',
		'opus' => 'audio/ogg',
	];

	/**
	 * Whether embeds are turned on: `supports.embeds` is `true` or an array
	 * of options.
	 *
	 * @since 1.1.3
	 */
	public static function enabled(): bool
	{
		$support = static::support();

		return true === $support || is_array( $support );
	}

	/**
	 * Returns an option from `supports.embeds`, or its default.
	 *
	 * @since 1.1.3
	 */
	public static function option( string $name, bool $default ): bool
	{
		$support = static::support();

		return is_array( $support ) && is_bool( $support[ $name ] ?? null )
			? $support[ $name ]
			: $default;
	}

	/**
	 * Returns the `supports.embeds` setting.
	 *
	 * @since 1.1.3
	 */
	protected static function support(): mixed
	{
		$key = 'app.supports.embeds';

		return Config::exists( $key ) ? Config::get( $key ) : false;
	}

	/**
	 * Returns the embed HTML for a URL, or `null` when it isn't something
	 * Novaris can embed.
	 *
	 * @since 1.1.3
	 */
	public static function html( string $url ): ?string
	{
		$url = trim( $url );

		if ( '' === $url || preg_match( '/\s/', $url ) ) {
			return null;
		}

		if ( $video = static::youtube( $url ) ) {
			return static::provider( 'youtube', 'YouTube', $url, $video );
		}

		if ( $video = static::vimeo( $url ) ) {
			return static::provider( 'vimeo', 'Vimeo', $url, $video );
		}

		return static::file( $url );
	}

	/**
	 * Returns the player URL and thumbnail for a YouTube link, or `null`.
	 *
	 * Handles youtube.com/watch?v=, youtu.be/, /shorts/, /embed/ and /live/
	 * links, with a start time from `t` or `start`.
	 *
	 * @since 1.1.3
	 * @return ?array{ src: string, thumbnail: ?string }
	 */
	protected static function youtube( string $url ): ?array
	{
		$parts = parse_url( $url );
		$host  = strtolower( preg_replace( '/^(www|m|music)\./i', '', $parts['host'] ?? '' ) );
		$path  = $parts['path'] ?? '';

		parse_str( $parts['query'] ?? '', $query );

		$id = null;

		if ( 'youtu.be' === $host ) {
			$id = trim( $path, '/' );
		} elseif ( in_array( $host, [ 'youtube.com', 'youtube-nocookie.com' ], true ) ) {
			if ( '/watch' === $path ) {
				$id = $query['v'] ?? null;
			} elseif ( preg_match( '#^/(?:shorts|embed|live|v)/([^/]+)#', $path, $matches ) ) {
				$id = $matches[1];
			}
		}

		if ( ! is_string( $id ) || ! preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ) {
			return null;
		}

		$host = static::option( 'privacy', true ) ? 'www.youtube-nocookie.com' : 'www.youtube.com';
		$args = [];

		if ( $start = static::seconds( $query['t'] ?? $query['start'] ?? null ) ) {
			$args['start'] = $start;
		}

		return [
			'src'       => "https://{$host}/embed/{$id}" . ( $args ? '?' . http_build_query( $args ) : '' ),
			'thumbnail' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg",
		];
	}

	/**
	 * Returns the player URL for a Vimeo link, or `null`.
	 *
	 * Handles vimeo.com/{id}, unlisted vimeo.com/{id}/{hash}, channel and
	 * group links, and player.vimeo.com/video/{id}.
	 *
	 * @since 1.1.3
	 * @return ?array{ src: string, thumbnail: ?string }
	 */
	protected static function vimeo( string $url ): ?array
	{
		$parts = parse_url( $url );
		$host  = strtolower( preg_replace( '/^www\./i', '', $parts['host'] ?? '' ) );
		$path  = $parts['path'] ?? '';

		if ( 'vimeo.com' === $host ) {
			$pattern = '#^/(?:(?:channels/[^/]+|groups/[^/]+/videos)/)?(\d+)(?:/([0-9a-f]+))?/?$#i';
		} elseif ( 'player.vimeo.com' === $host ) {
			$pattern = '#^/video/(\d+)/?$#';
		} else {
			return null;
		}

		if ( ! preg_match( $pattern, $path, $matches ) ) {
			return null;
		}

		parse_str( $parts['query'] ?? '', $query );

		$args = [];

		if ( ! empty( $matches[2] ) ) {
			$args['h'] = $matches[2];
		} elseif ( isset( $query['h'] ) && is_string( $query['h'] ) && preg_match( '/^[0-9a-f]+$/i', $query['h'] ) ) {
			$args['h'] = $query['h'];
		}

		if ( static::option( 'privacy', true ) ) {
			$args['dnt'] = 1;
		}

		$src = "https://player.vimeo.com/video/{$matches[1]}" . ( $args ? '?' . http_build_query( $args ) : '' );

		// Vimeo thumbnails need its API, which would mean fetching from the
		// internet while building, so Vimeo embeds show a plain play button.
		return [ 'src' => $src, 'thumbnail' => null ];
	}

	/**
	 * Returns the HTML for a YouTube or Vimeo video.
	 *
	 * @since 1.1.3
	 * @param array{ src: string, thumbnail: ?string } $video
	 */
	protected static function provider( string $slug, string $name, string $url, array $video ): string
	{
		$e = fn( string $value ) => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

		if ( ! static::option( 'click_to_load', true ) ) {
			return sprintf(
				'<figure class="embed embed--%1$s"><div class="embed__frame"><iframe class="embed__iframe" src="%2$s" title="%3$s video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; fullscreen; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></figure>',
				$e( $slug ),
				$e( $video['src'] ),
				$e( $name )
			);
		}

		$thumbnail = $video['thumbnail']
			? sprintf( '<img class="embed__thumbnail" src="%s" alt="" loading="lazy" decoding="async" width="480" height="360">', $e( $video['thumbnail'] ) )
			: '';

		return sprintf(
			'<figure class="embed embed--%1$s embed--click-to-load"><div class="embed__frame"><a class="embed__play" href="%2$s" data-embed-src="%3$s" data-embed-title="%4$s video">%5$s<span class="embed__play-button" aria-hidden="true"></span><span class="embed__label">Play video on %4$s</span></a></div></figure>',
			$e( $slug ),
			$e( $url ),
			$e( $video['src'] ),
			$e( $name ),
			$thumbnail
		);
	}

	/**
	 * Returns a `<video>` or `<audio>` player for a link to a media file, or
	 * `null` for any other link.
	 *
	 * @since 1.1.3
	 */
	protected static function file( string $url ): ?string
	{
		$path      = (string) parse_url( $url, PHP_URL_PATH );
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( isset( static::VIDEO[ $extension ] ) ) {
			$tag  = 'video';
			$type = static::VIDEO[ $extension ];
		} elseif ( isset( static::AUDIO[ $extension ] ) ) {
			$tag  = 'audio';
			$type = static::AUDIO[ $extension ];
		} else {
			return null;
		}

		// Only links to this site's files or to other sites over HTTP(S).
		if ( ! preg_match( '#^(https?://|/)#i', $url ) || str_starts_with( $url, '//' ) ) {
			return null;
		}

		// Links to the site's own files, such as `/user/media/clip.mp4`,
		// get the file's real URL, which can differ with `NOVARIS_USER_PATH`.
		if ( str_starts_with( $url, '/' ) ) {
			$media = new Media( $url );
			$url   = $media->url() ?: $url;
		}

		$e = fn( string $value ) => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

		return sprintf(
			'<figure class="embed embed--%1$s"><%1$s class="embed__player" controls preload="metadata"%4$s><source src="%2$s" type="%3$s"><a href="%2$s">Download the %1$s</a></%1$s></figure>',
			$tag,
			$e( $url ),
			$e( $type ),
			'video' === $tag ? ' playsinline' : ''
		);
	}

	/**
	 * Converts a YouTube start time (`90`, `90s` or `1h2m30s`) to seconds.
	 *
	 * @since 1.1.3
	 */
	protected static function seconds( mixed $time ): int
	{
		if ( ! is_string( $time ) || '' === $time ) {
			return 0;
		}

		if ( ctype_digit( $time ) ) {
			return (int) $time;
		}

		if ( ! preg_match( '/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/i', $time, $matches ) ) {
			return 0;
		}

		return (int) ( $matches[1] ?? 0 ) * 3600 + (int) ( $matches[2] ?? 0 ) * 60 + (int) ( $matches[3] ?? 0 );
	}
}
