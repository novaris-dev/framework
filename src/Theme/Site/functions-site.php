<?php
namespace Novaris\Theme\Site;

/**
 * Outputs the site title HTML for Novaris.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @return void
 */
function display_site_title( array $args = [] ): void {

    echo render_site_title( $args );
}

/**
 * Returns the site title HTML for Novaris.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @return string
 */
function render_site_title( array $args = [] ): string {

    // Default arguments
    $args = array_merge( [
        'tag'       => 'h1',
        'class'     => 'site-title',
        'link_class' => 'site-title-link'
    ], $args );

    // Placeholder for site title; replace with Novaris equivalent.
    $title = config( 'app.title' );

    if ( !$title ) {
        return ''; // Return empty if there's no title set.
    }

    // Build the link for the site title.
    $link = render_home_link( [
        'text' => $title,
        'class' => $args['link_class']
    ]);

    // Construct the HTML.
    $html = sprintf(
        '<%1$s class="%2$s">%3$s</%1$s>',
        htmlspecialchars( $args['tag'], ENT_QUOTES, 'UTF-8' ),
        htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
        $link
    );

    return $html;
}
