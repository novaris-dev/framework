<?php
/**
 * Navigation template tag.
 *
 * Handles rendering navigation menus and determining the current menu item.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

use Novaris\Contracts\{Displayable, Renderable};
use Symfony\Component\HttpFoundation\Request;

use function Novaris\Theme\Menu\normalize_path;

class Navigation implements Displayable, Renderable
{
	/**
	 * Navigation items.
	 *
	 * @since 1.0.0
	 */
	protected array $items;

	/**
	 * Navigation display options.
	 *
	 * @since 1.0.0
	 */
	protected array $display;

	/**
	 * Current request path.
	 *
	 * @since 1.0.0
	 */
	protected string $currentPath;

	/**
	 * Sets up the object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $items = [], array $options = [] )
	{
		// Use Symfony Request to get the current path.
		$request = Request::createFromGlobals();

		$this->currentPath = normalize_path( $request->getPathInfo() );

		// Initialize items and display settings.
		$this->items = $items;

		$this->display = array_merge( [
			'nav_class'     => 'navigation',
			'list_tag'      => 'ul',
			'list_class'    => 'navigation__list',
			'item_tag'      => 'li',
			'item_class'    => 'navigation__item',
			'anchor_class'  => 'navigation__link',
			'current_class' => 'navigation__item--current'
		], $options );
	}

	/**
	 * Sets the navigation items.
	 *
	 * @since 1.0.0
	 */
	public function setItems( array $items ): void
	{
		$this->items = $items;
	}

	/**
	 * Displays the navigation.
	 *
	 * @since 1.0.0
	 */
	public function display(): void
	{
		echo $this->render();
	}

	/**
	 * Renders the navigation.
	 *
	 * @since 1.0.0
	 */
	public function render(): string
	{
		$listItems = array_map(
			[ $this, 'formatItem' ],
			array_keys( $this->items ),
			$this->items
		);

		return sprintf(
			'<nav class="%s"><%s class="%s">%s</%s></nav>',
			e( $this->display['nav_class'] ),
			escape_tag( $this->display['list_tag'] ),
			e( $this->display['list_class'] ),
			implode( '', $listItems ),
			escape_tag( $this->display['list_tag'] )
		);
	}

	/**
	 * Formats a navigation item.
	 *
	 * @since 1.0.0
	 */
	private function formatItem( string $name, string $url ): string
	{
		$itemPath  = normalize_path( uri( $url ) );
		$isCurrent = $this->currentPath === $itemPath;

		$itemClass = trim(
			$this->display['item_class']
			. ( $isCurrent ? " {$this->display['current_class']}" : '' )
		);

		$ariaCurrent = $isCurrent
			? ' aria-current="page"'
			: '';

		return sprintf(
			'<%1$s class="%2$s"><a href="%3$s" class="%4$s"%5$s>%6$s</a></%1$s>',
			escape_tag( $this->display['item_tag'] ),
			e( $itemClass ),
			e( $url ),
			e( $this->display['anchor_class'] ),
			$ariaCurrent,
			e( $name )
		);
	}
}