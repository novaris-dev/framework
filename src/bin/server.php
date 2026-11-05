<?php
/**
 * Router script for `novaris serve`, which runs PHP's built-in web server.
 *
 * Files that exist in the site folder, such as theme assets, are served as
 * they are. Every other request goes to the site's `index.php`. That includes
 * `/user/media` files when `NOVARIS_USER_PATH` points at another folder, which
 * the built-in server would otherwise answer with its own 404.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

$root = rtrim( $_SERVER['DOCUMENT_ROOT'], '/\\' );
$path = rawurldecode( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );

if ( '/' !== $path && is_file( $root . $path ) ) {
	return false;
}

$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';

chdir( $root );

require $root . '/index.php';
