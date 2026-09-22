<?php
/**
 * Site template functions.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Theme\Site;

/**
 * Outputs the site title HTML for Novaris.
 *
 * @since 1.0.0
 *
 * @param array $args Site title arguments.
 */
function display_site_title( array $args = [] ): void
{
	echo render_site_title( $args );
}

/**
 * Returns the site title HTML for Novaris.
 *
 * @since 1.0.0
 *
 * @param  array $args Site title arguments.
 * @return string
 */
function render_site_title( array $args = [] ): string
{
	$args = array_merge( [
		'tag'        => 'h1',
		'class'      => 'branding__title',
		'link_class' => 'branding__link'
	], $args );

	$title = config( 'app.title' );

	if ( ! $title ) {
		return '';
	}

	$link = render_home_link( [
		'text'  => $title,
		'class' => $args['link_class']
	] );

	return sprintf(
		'<%1$s class="%2$s">%3$s</%1$s>',
		htmlspecialchars( $args['tag'], ENT_QUOTES, 'UTF-8' ),
		htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
		$link
	);
}

/**
 * Outputs the site description HTML for Novaris.
 *
 * @since 1.0.0
 *
 * @param array $args Site description arguments.
 */
function display_site_description( array $args = [] ): void
{
	echo render_site_description( $args );
}

/**
 * Returns the site description HTML for Novaris.
 *
 * @since 1.0.0
 *
 * @param  array $args Site description arguments.
 * @return string
 */
function render_site_description( array $args = [] ): string
{
	$args = array_merge( [
		'tag'   => 'span',
		'class' => 'branding__description'
	], $args );

	$description = config( 'app.tagline' );

	if ( ! $description ) {
		return '';
	}

	return sprintf(
		'<%1$s class="%2$s">%3$s</%1$s>',
		htmlspecialchars( $args['tag'], ENT_QUOTES, 'UTF-8' ),
		htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
		htmlspecialchars( $description, ENT_QUOTES, 'UTF-8' )
	);
}

/**
 * Outputs the home link HTML for Novaris.
 *
 * @since 1.0.0
 *
 * @param array $args Home link arguments.
 */
function display_home_link( array $args = [] ): void
{
	echo render_home_link( $args );
}

/**
 * Returns the home link HTML for Novaris.
 *
 * @since 1.0.0
 *
 * @param  array $args Home link arguments.
 * @return string
 */
function render_home_link( array $args = [] ): string
{
	$args = array_merge( [
		'text'   => '%s',
		'class'  => 'branding__link',
		'before' => '',
		'after'  => ''
	], $args );

	$home_url = e( uri() );

	$html = sprintf(
		'<a class="%s" href="%s" rel="home">%s</a>',
		htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
		$home_url,
		htmlspecialchars( $args['text'], ENT_QUOTES, 'UTF-8' )
	);

	return $args['before'] . $html . $args['after'];
}

/**
 * Returns the custom header image URL.
 *
 * @since 1.0.0
 *
 * @return string Custom header image URL or an empty string if the theme
 *                does not support custom headers.
 */
function custom_header(): string
{
	$metadata = app( 'theme.metadata' );

	if ( ! $metadata->supports( 'custom-header' ) ) {
		return '';
	}

	$support = $metadata->support( 'custom-header' );

	return $support['default'] ?? '';
}

/**
 * Returns the featured image for a content entry.
 *
 * @since 1.0.0
 *
 * @param  \Novaris\Contracts\Content\ContentEntry $entry Content entry.
 * @return \Novaris\Tools\Media|null                       Featured image or null.
 */
function featured_image(
	\Novaris\Contracts\Content\ContentEntry $entry
): \Novaris\Tools\Media|null
{
	$metadata = app( 'theme.metadata' );

	if ( ! $metadata->supports( 'featured-image' ) ) {
		return null;
	}

	return $entry->featuredImage();
}