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
		// Don't display breadcrumbs on the homepage.
		if ( is_home() ) {
			return '';
		}

		// Bail if there is no single entry available.
		if ( ! $this->data->has( 'single' ) ) {
			return '';
		}

		$single = $this->data->get( 'single' );

		if ( ! $single ) {
			return '';
		}

		$html  = '<nav class="breadcrumbs" aria-label="Breadcrumbs">';
		$html .= '<ol class="breadcrumbs-list">';

		// Home.
		$html .= sprintf(
			'<li class="breadcrumbs-item"><a href="%s">Home</a></li>',
			url()
		);

		// Current entry.
		$html .= sprintf(
			'<li class="breadcrumbs-item" aria-current="page">%s</li>',
			$single->title()
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