<?php

namespace Novaris\Theme\Menu;

use Novaris\Core\Proxies\App;

function normalize_path( $value ): string
{
	$value = (string) $value;

	$path = parse_url( $value, PHP_URL_PATH );

	if ( $path === null || $path === false ) {
		$path = $value;
	}

	$path = '/' . ltrim( $path, '/' );
	$path = rtrim( $path, '/' );

	return $path === '' ? '/' : $path;
}

function display_nav_menu( $args = [] )
{
	// Default arguments.
	$defaults = [
		'menu'            => '',
		'container'       => 'nav',
		'container_id'    => '',
		'container_class' => '',
		'menu_id'         => '',
		'menu_class'      => '',
		'echo'            => true,
		'fallback_cb'     => false,
		'theme_location'  => ''
	];

	// Merge user-defined args with defaults.
	$args = array_merge( $defaults, $args );

	// Retrieve menu items.
	$items = config( "app.{$args['theme_location']}" );

	// If no items exist and a fallback is set, call it.
	if ( ! $items && is_callable( $args['fallback_cb'] ) ) {
		call_user_func( $args['fallback_cb'] );
		return;
	}

	// Get the current URL path.
	$currentPath = normalize_path(
		App::resolve( 'routing.router' )->path()
	);

	// Build the menu.
	ob_start();

	if ( $args['container'] ) {
		echo "<{$args['container']} id=\""
			. htmlspecialchars( $args['container_id'], ENT_QUOTES, 'UTF-8' )
			. "\" class=\""
			. htmlspecialchars( $args['container_class'], ENT_QUOTES, 'UTF-8' )
			. "\">";
	}

	echo '<button class="menu-toggle" aria-controls="'
		. htmlspecialchars( $args['menu_id'], ENT_QUOTES, 'UTF-8' )
		. '" aria-expanded="false">Menu</button>';

	echo '<ul id="'
		. htmlspecialchars( $args['menu_id'], ENT_QUOTES, 'UTF-8' )
		. '" class="'
		. htmlspecialchars( $args['menu_class'], ENT_QUOTES, 'UTF-8' )
		. '">';

	foreach ( $items as $name => $url ) {
		$fullUrl  = uri( $url );
		$itemPath = normalize_path( $url );

		$class = 'menu-items__item';

		if ( $currentPath === $itemPath ) {
			$class .= ' menu-items__item--current';
		}

		echo '<li class="' . htmlspecialchars( $class, ENT_QUOTES, 'UTF-8' ) . '">';

		echo '<a class="menu-items__item-anchor" href="'
			. e( $fullUrl )
			. '">'
			. htmlspecialchars( $name, ENT_QUOTES, 'UTF-8' )
			. '</a>';

		echo '</li>';
	}

	echo '</ul>';

	if ( $args['container'] ) {
		echo "</{$args['container']}>";
	}

	$output = ob_get_clean();

	if ( $args['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}