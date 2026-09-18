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
		return new View(
			$this,
			$name,
			$hierarchy,
			$data
		);
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
	 * Loop through an iterable and include a view for each item.
	 *
	 * @since 1.0.0
	 */
	public function each(
		string $name,
		iterable $items = [],
		string $var = '',
		array|string $hierarchy = []
	): void {
		foreach ( $items as $item ) {
			$this->include(
				$name,
				$hierarchy,
				$var ? [ $var => $item ] : []
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