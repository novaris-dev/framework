<?php
/**
 * View engine.
 *
 * Handles creating, displaying, and rendering views.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\View;

use Novaris\Tools\Collection;

class Engine
{
	/**
	 * Create a new view.
	 *
	 * @since 1.0.0
	 */
	public function make(
		string $name,
		array|string $hierarchy = [],
		array|Collection $data = []
	): View {
		if ( str_contains( $name, '.' ) ) {
			$name      = str_replace( '.', '/', $name );
			$hierarchy = [];
		}

		return new View(
			$this,
			$name,
			$hierarchy,
			$data
		);
	}

	/**
	 * Determine whether a view exists.
	 *
	 * @since 1.0.0
	 */
	public function exists(
		string $name,
		array|string $hierarchy = []
	): bool {
		return null !== $this->make(
			$name,
			$hierarchy
		)->template();
	}

	/**
	 * Display a view.
	 *
	 * @since 1.0.0
	 */
	public function include(
		string $name,
		array|string $hierarchy = [],
		array|Collection $data = []
	): void {
		$this->make(
			$name,
			$hierarchy,
			$data
		)->display();
	}

	/**
	 * Display a view only if it exists.
	 *
	 * @since 1.0.0
	 */
	public function includeIf(
		string $name,
		array|string $hierarchy = [],
		array|Collection $data = []
	): void {
		$view = $this->make(
			$name,
			$hierarchy,
			$data
		);

		if ( $view->template() ) {
			$view->display();
		}
	}

	/**
	 * Display a view when the given condition is true.
	 *
	 * @since 1.0.0
	 */
	public function includeWhen(
		mixed $when,
		string $name,
		array|string $hierarchy = [],
		array|Collection $data = []
	): void {
		if ( $when ) {
			$this->include(
				$name,
				$hierarchy,
				$data
			);
		}
	}

	/**
	 * Display a view unless the given condition is true.
	 *
	 * @since 1.0.0
	 */
	public function includeUnless(
		mixed $unless,
		string $name,
		array|string $hierarchy = [],
		array|Collection $data = []
	): void {
		if ( ! $unless ) {
			$this->include(
				$name,
				$hierarchy,
				$data
			);
		}
	}

	/**
	 * Loop through an iterable and include a view for each item.
	 *
	 * An optional empty view may be displayed when there are no items.
	 *
	 * @since 1.0.0
	 */
	public function each(
		string $name,
		iterable $items = [],
		string $var = '',
		array|string $hierarchy = [],
		string $empty = '',
		array|Collection $data = []
	): void {
		$hasItems = false;

		foreach ( $items as $item ) {
			$hasItems = true;

			$itemData = $data instanceof Collection
				? $data->all()
				: $data;

			if ( $var ) {
				$itemData[ $var ] = $item;
			}

			$this->include(
				$name,
				$hierarchy,
				$itemData
			);
		}

		if ( ! $hasItems && $empty ) {
			$this->include(
				$empty,
				[],
				$data
			);
		}
	}

	/**
	 * Render a view.
	 *
	 * @since 1.0.0
	 */
	public function render(
		string $name,
		array|string $hierarchy = [],
		array|Collection $data = []
	): string {
		return $this->make(
			$name,
			$hierarchy,
			$data
		)->render();
	}
}