<?php
/**
 * Menu functions.
 *
 * Provides helper functions for working with and displaying navigation menus.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Theme\Menu;

use Novaris\Template\Tag\Navigation;

/**
 * Normalizes a URL or URI to a comparable path.
 *
 * @since 1.0.0
 */
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

/**
 * Displays or returns a navigation menu.
 *
 * @since 1.0.0
 */
function display( $args = [] )
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