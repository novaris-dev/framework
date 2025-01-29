<?php

namespace Novaris\Theme\Menu;

function display_nav_menu( $args = [] ) {

    // Default arguments
    $defaults = [
        'menu'            => '',
        'container'       => 'nav',
        'container_id'    => '',
        'container_class' => '',
        'menu_id'         => '',
        'menu_class'      => '',
        'echo'            => true,
        'fallback_cb'     => false // Set to a function name for fallback
    ];

    // Merge user-defined args with defaults
    $args = array_merge( $defaults, $args );

    // Retrieve menu items
    $items = config( "app.{$args['menu']}" ); // Assuming this returns an array

    // If no items exist and a fallback is set, call it
    if (!$items && is_callable($args['fallback_cb'])) {
        call_user_func($args['fallback_cb']);
        return;
    }

    // Get the current URL path
    $currentPath = $_SERVER['REQUEST_URI'];

    // Build the menu
    ob_start();
    if ($args['container']) {
        echo "<{$args['container']} id=\"" . htmlspecialchars($args['container_id'], ENT_QUOTES, 'UTF-8') . "\" class=\"" . htmlspecialchars($args['container_class'], ENT_QUOTES, 'UTF-8') . "\">";
    }

    echo '<button class="menu-toggle" aria-controls="' . htmlspecialchars($args['menu_id'], ENT_QUOTES, 'UTF-8') . '" aria-expanded="false">Menu</button>';
    echo '<ul id="' . htmlspecialchars($args['menu_id'], ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($args['menu_class'], ENT_QUOTES, 'UTF-8') . '">';

    foreach ($items as $name => $url) {
        $class = ($currentPath == $url) ? 'menu-item current-menu-item' : 'menu-item';
        echo '<li class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
        echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
        echo '</li>';
    }

    echo '</ul>';

    if ($args['container']) {
        echo "</{$args['container']}>";
    }

    $output = ob_get_clean();

    if ($args['echo']) {
        echo $output;
    } else {
        return $output;
    }
}