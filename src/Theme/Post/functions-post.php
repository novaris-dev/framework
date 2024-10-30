<?php
namespace Novaris\Theme\Post;

/**
 * Outputs the post title HTML for Novaris.
 *
 * @param  array $args Optional arguments to override defaults.
 * @return void
 */
function display_title( array $args = [] ): void {
    echo render_title( $args );
}

/**
 * Returns the post title HTML for Novaris.
 *
 * @param  array $args Optional arguments to override defaults.
 * @return string
 */
function render_title( array $args = [] ): string {

    // Set a default title if none is provided in $args
    $args['title'] = $args['title'] ?? '';

    // Merge additional defaults for `class`, `before`, `after`, and `tag`
    $args = array_merge([
        'after'  => '',
        'before' => '',
        'class'  => 'entry-title',
        'tag'    => 'h2',
        'text'   => '%s',
        'link'   => false,
    ], $args);

    // Format the title text
    $text = sprintf( $args['text'], htmlspecialchars( $args['title'], ENT_QUOTES, 'UTF-8' ) );

    // Optionally wrap the title in a link if 'link' and 'url' are set
    if ( $args['link'] && isset( $args['url'] ) ) {
        $text = sprintf(
            '<a href="%s" class="title-link">%s</a>',
            htmlspecialchars( $args['url'], ENT_QUOTES, 'UTF-8' ),
            $text
        );
    }

    // Construct the final HTML for the title
    $html = sprintf(
        '<%1$s class="%2$s">%3$s</%1$s>',
        htmlspecialchars( $args['tag'], ENT_QUOTES, 'UTF-8' ),
        htmlspecialchars( $args['class'], ENT_QUOTES, 'UTF-8' ),
        $text
    );

    // Return the final HTML, including before and after content
    return $args['before'] . $html . $args['after'];
}
