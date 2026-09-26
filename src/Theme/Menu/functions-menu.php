<?php

namespace Novaris\Theme\Menu;

use Novaris\Template\Tag\Navigation;

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

	$args = array_merge( $defaults, $args );

	$items = config( "app.{$args['theme_location']}" );

	if ( ! $items && is_callable( $args['fallback_cb'] ) ) {
		call_user_func( $args['fallback_cb'] );
		return;
	}

	$navigation = new Navigation( $items, [
		'nav_id'     => $args['container_id'],
		'nav_class'  => $args['container_class'],
		'list_id'    => $args['menu_id'],
		'list_class' => $args['menu_class']
	] );

	if ( $args['echo'] ) {
		$navigation->display();
		return;
	}

	return $navigation->render();
}