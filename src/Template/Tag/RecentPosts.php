<?php
/**
 * Recent posts template tag.
 *
 * Displays a list of recent posts.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

use Novaris\Query;

class RecentPosts extends Tag
{
	/**
	 * Sets up the object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct( protected int $number = 5 ) {}

	/**
	 * Returns the recent posts as HTML.
	 *
	 * @since 1.0.0
	 */
	public function toHtml(): string
	{
		$collection = Query::make( [
			'type'      => 'post',
			'number'    => $this->number,
			'order'     => 'desc',
			'orderby'   => 'date',
			'nocontent' => true,
		] );

		$html  = '<div id="recent-posts" class="widget widget_recent_posts">';
		$html .= '<h3 class="widget-title">Recent Posts</h3>';
		$html .= '<ul>';

		if ( ! empty( $collection ) ) {
			foreach ( $collection as $entry ) {
				$html .= sprintf(
					'<li><a href="%s">%s</a></li>',
					$entry->uri(),
					$entry->title()
				);
			}
		} else {
			$html .= '<li>No recent posts available.</li>';
		}

		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the recent posts as text.
	 *
	 * @since 1.0.0
	 */
	public function toText(): string
	{
		return strip_tags( $this->toHtml() );
	}
}