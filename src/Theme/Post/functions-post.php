<?php
namespace Novaris\Theme\Post;

/**
 * Outputs the post title HTML for Novaris.
 *
 * @param  array $args
 * @return void
 */
function display_title( array $args = [] ): void {
    echo render_title( $args );
}

/**
 * Returns the post title HTML for Novaris.
 *
 * @param  array $args
 * @return string
 */
function render_title( array $args = [] ): string {

    // Set default arguments with 'tag' passed in to set h1 or h2 based on context
    $args = array_merge( [
        'after'  => '',
        'before' => '',
        'class'  => 'entry-title',
        'link'   => false, // Default to no link
        'tag'    => 'h2',  // Default to h2, can be set to h1 in single views
        'text'   => '%s',
    ], $args );

    // Assume title is passed in $args; otherwise, use a fallback
    $title = $args['title'] ?? 'Default Title';

    // Format the title text
    $text = sprintf( $args['text'], htmlspecialchars( $title, ENT_QUOTES, 'UTF-8' ) );

    // Optionally wrap the title in a link if 'link' is true
    if ( $args['link'] ) {
        $text = sprintf(
            '<a href="/" class="title-link">%s</a>',
            $text
        );
    }

    // Construct the HTML output
    $html = sprintf(
        '<%1$s class="%2$s">%3$s</%1$s>',
        htmlspecialchars( $args['tag'], ENT_QUOTES, 'UTF-8' ),
        htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
        $text
    );

    return $args['before'] . $html . $args['after'];
}
