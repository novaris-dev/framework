<?php
/**
 * Categories template tag.
 *
 * Displays a list of categories.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

use Novaris\Query;

class Categories extends Tag
{
	/**
	 * Returns the categories as HTML.
	 *
	 * @since 1.0.0
	 */
	public function toHtml(): string
	{
		$categories = Query::make( [
			'type'      => 'category',
			'number'    => PHP_INT_MAX,
			'order'     => 'desc',
			'orderby'   => 'date',
			'nocontent' => true,
		] );

		$html  = '<div id="categories" class="widget widget_categories">';
		$html .= '<h3 class="widget-title">Categories</h3>';
		$html .= '<ul>';

		foreach ( $categories as $entry ) {
			$html .= sprintf(
				'<li><a href="%s">%s</a></li>',
				$entry->uri(),
				$entry->name()
			);
		}

		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the categories as text.
	 *
	 * @since 1.0.0
	 */
	public function toText(): string
	{
		return strip_tags( $this->toHtml() );
	}
}