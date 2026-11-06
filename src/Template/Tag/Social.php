<?php
/**
 * Social template tag.
 *
 * Displays the links from the `social` list in `config/app.php`. Use it in
 * views with `$engine->social()`.
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
	 * Stores the links, as label and URL pairs.
	 *
	 * @since 1.0.10
	 */
	protected array $items;

	/**
	 * Sets up the links. Uses the `social` config when none are passed in.
	 *
	 * @since 1.0.10
	 */
	public function __construct( ?array $items = null )
	{
		$this->items = $items ?? ( config( 'app.social' ) ?? [] );
	}

	/**
	 * Returns the links as HTML, or an empty string when there are none.
	 *
	 * @since 1.0.10
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
	 * @since 1.0.10
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
	 * @since 1.0.10
	 */
	private function formatItem( string $label, string $url ): string
	{
		$href = preg_match( '#^(?:https?:|mailto:|//)#i', $url ) ? $url : url( $url );

		return sprintf(
			'<li><a href="%s" rel="me noopener">%s</a></li>',
			e( $href ),
			e( $label )
		);
	}
}
