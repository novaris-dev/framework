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

/**
 * Outputs the site description HTML for Novaris.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @return void
 */
function display_site_description( array $args = [] ): void {

    echo render_site_description( $args );
}

/**
 * Returns the site description HTML for Novaris.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @return string
 */
function render_site_description( array $args = [] ): string {

    // Set default arguments
    $args = array_merge( [
        'tag'    => 'span',
        'class'  => 'site-description',
    ], $args );

    // Retrieve the site description
    $description = config( 'app.tagline' );

    if ( !$description ) {
        return ''; // Return empty if there is no description
    }

    // Construct the HTML
    $html = sprintf(
        '<%1$s class="%2$s">%3$s</%1$s>',
        htmlspecialchars( $args['tag'], ENT_QUOTES, 'UTF-8' ),
        htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
        htmlspecialchars( $description, ENT_QUOTES, 'UTF-8' )
    );

    return $html;
}

/**
 * Outputs the site link HTML for Novaris.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @return void
 */
function display_home_link( array $args = [] ): void {

    echo render_home_link( $args );
}

/**
 * Returns the site link HTML for Novaris.
 *
 * @since  1.0.0
 * @access public
 * @param  array  $args
 * @return string
 */
function render_home_link( array $args = [] ): string {

    // Set default arguments
    $args = array_merge( [
        'text'   => '%s',
        'class'  => 'home-link',
        'before' => '',
        'after'  => ''
    ], $args );

    // Get the site URL
    $home_url = e( uri() );

    // Assume home URL and site name are handled in display_site_title
    $html = sprintf(
        '<a class="%s" href="%s" rel="home">%s</a>',
        htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
        $home_url,
        sprintf( $args['text'], htmlspecialchars( $args['text'], ENT_QUOTES, 'UTF-8' ) )
    );

    // Return the final HTML, including 'before' and 'after' elements
    return $args['before'] . $html . $args['after'];
}
