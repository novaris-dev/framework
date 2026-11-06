<?php
/**
 * Social template tag.
 *
 * Displays the links from the `social` list in `config/app.php`. Use it in
 * views with `$engine->social()`. Known sites get an inline SVG icon, chosen
 * from the link's address, and any other link shows its label. A theme can
 * replace an icon with a file in `resources/svg/social`.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

class Social extends Tag
{
	/**
	 * Icon files for each site.
	 *
	 * @since 1.1.1
	 */
	protected const ICONS = [
		'behance.net'    => 'behance',
		'codepen.io'     => 'codepen',
		'dribbble.com'   => 'dribbble',
		'facebook.com'   => 'facebook',
		'github.com'     => 'github',
		'instagram.com'  => 'instagram',
		'linkedin.com'   => 'linkedin',
		'medium.com'     => 'medium',
		'pinterest.com'  => 'pinterest',
		'reddit.com'     => 'reddit',
		'snapchat.com'   => 'snapchat',
		'soundcloud.com' => 'soundcloud',
		'spotify.com'    => 'spotify',
		'tumblr.com'     => 'tumblr',
		'twitch.tv'      => 'twitch',
		'twitter.com'    => 'twitter',
		'vimeo.com'      => 'vimeo',
		'wordpress.com'  => 'wordpress',
		'wordpress.org'  => 'wordpress',
		'youtube.com'    => 'youtube',
		'youtu.be'       => 'youtube',
	];

	/**
	 * Stores the links, as label and URL pairs.
	 *
	 * @since 1.1.0
	 */
	protected array $items;

	/**
	 * Sets up the links. Uses the `social` config when none are passed in.
	 *
	 * @since 1.1.0
	 */
	public function __construct( ?array $items = null )
	{
		$this->items = $items ?? ( config( 'app.social' ) ?? [] );
	}

	/**
	 * Returns the links as HTML, or an empty string when there are none.
	 *
	 * @since 1.1.0
	 */
	public function toHtml(): string
	{
		if ( ! $this->items ) {
			return '';
		}

		$items = array_map(
			[ $this, 'formatItem' ],
			array_keys( $this->items ),
			$this->items
		);

		return sprintf(
			'<nav class="social-menu" aria-label="Social"><ul>%s</ul></nav>',
			implode( '', $items )
		);
	}

	/**
	 * Returns the link labels as plain text.
	 *
	 * @since 1.1.0
	 */
	public function toText(): string
	{
		return implode( ', ', array_keys( $this->items ) );
	}

	/**
	 * Returns a single link. These are usually other sites, so web and email
	 * links stay as they are, while anything else, such as `/about`, is treated
	 * as a path on this site.
	 *
	 * @since 1.1.0
	 */
	private function formatItem( string $label, string $url ): string
	{
		$href = preg_match( '#^(?:https?:|mailto:|//)#i', $url ) ? $url : url( $url );

		$icon = $this->icon( $url );

		return sprintf(
			'<li><a href="%s" rel="me noopener">%s</a></li>',
			e( $href ),
			$icon
				? $icon . '<span class="screen-reader-text">' . e( $label ) . '</span>'
				: e( $label )
		);
	}

	/**
	 * Returns the SVG for a link, or an empty string if it doesn't have one.
	 *
	 * @since 1.1.1
	 */
	private function icon( string $url ): string
	{
		$name = $this->iconName( $url );

		if ( ! $name ) {
			return '';
		}

		$paths = [ resource_path( "svg/social/{$name}.svg" ) ];

		if ( ! config( 'app.private' ) ) {
			$paths[] = theme_path( "resources/svg/social/{$name}.svg" );
		}

		$paths[] = dirname( __DIR__ ) . "/Icons/social/{$name}.svg";

		foreach ( $paths as $path ) {
			if ( is_file( $path ) ) {
				return trim( (string) file_get_contents( $path ) );
			}
		}

		return '';
	}

	/**
	 * Returns the icon name for a link, from its address.
	 *
	 * @since 1.1.1
	 */
	private function iconName( string $url ): string
	{
		if ( 0 === stripos( $url, 'mailto:' ) ) {
			return 'email';
		}

		if ( preg_match( '#/(?:feed|rss)(?:\.xml)?/?$#i', (string) parse_url( $url, PHP_URL_PATH ) ) ) {
			return 'rss';
		}

		$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) );

		foreach ( self::ICONS as $domain => $name ) {
			if ( $host === $domain || str_ends_with( $host, ".{$domain}" ) ) {
				return $name;
			}
		}

		return '';
	}
}
