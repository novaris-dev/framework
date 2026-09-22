<?php
/**
 * Breadcrumbs template tag.
 *
 * Displays breadcrumb navigation for the current request.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

class Breadcrumbs extends Tag
{
	/**
	 * Returns the breadcrumbs as HTML.
	 *
	 * @since 1.0.0
	 */
	public function toHtml(): string
	{
		$html  = '<nav class="breadcrumbs" aria-label="Breadcrumbs">';
		$html .= '<ol class="breadcrumbs-list">';
		$html .= sprintf(
			'<li class="breadcrumbs-item"><a href="%s">Home</a></li>',
			url()
		);
		$html .= '</ol>';
		$html .= '</nav>';

		return $html;
	}

	/**
	 * Returns the breadcrumbs as text.
	 *
	 * @since 1.0.0
	 */
	public function toText(): string
	{
		return strip_tags( $this->toHtml() );
	}
}