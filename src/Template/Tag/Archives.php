<?php
/**
 * Archives template tag.
 *
 * Displays a list of monthly post archives.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

use Novaris\Query;

class Archives extends Tag
{
	/**
	 * Returns the archives as HTML.
	 *
	 * @since 1.0.0
	 */
	public function toHtml(): string
	{
		$collection = Query::make( [
			'type'      => 'post',
			'number'    => PHP_INT_MAX,
			'order'     => 'desc',
			'orderby'   => 'date',
			'nocontent' => true,
		] );

		$html  = '<div id="archives" class="widget widget_archives">';
		$html .= '<h3 class="widget-title">Archives</h3>';
		$html .= '<ul>';

		$current_year  = '';
		$current_month = '';

		if ( ! empty( $collection ) ) {
			foreach ( $collection as $entry ) {
				$timestamp = $entry->metaSingle( 'date' );

				if ( ! is_numeric( $timestamp ) ) {
					$timestamp = strtotime( $timestamp );
				}

				$year         = date( 'Y', $timestamp );
				$month        = date( 'F', $timestamp );
				$month_number = date( 'm', $timestamp );

				if ( $current_month !== $month || $current_year !== $year ) {
					$current_month = $month;
					$current_year  = $year;

					$archive_url = rtrim( config( 'app.uri' ), '/' ) . '/' . $year . '/' . $month_number . '/';

					$html .= sprintf(
						'<li><a href="%s">%s %s</a></li>',
						$archive_url,
						$month,
						$year
					);
				}
			}
		} else {
			$html .= '<li>No archives available.</li>';
		}

		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the archives as text.
	 *
	 * @since 1.0.0
	 */
	public function toText(): string
	{
		return strip_tags( $this->toHtml() );
	}
}