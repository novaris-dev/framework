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

use Novaris\Contracts\Template\{TemplateTag, TemplateTags};
use Novaris\Core\Proxies\Message;
use Novaris\Tools\Collection;

class Engine
{
	/**
	 * Current view data stack.
	 *
	 * @since 1.0.0
	 */
	protected array $dataStack = [];

	/**
	 * Create a new view engine.
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		protected TemplateTags $tags
	) {}

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

		$data = array_merge(
			$this->data()->all(),
			$data instanceof Collection
				? $data->all()
				: $data
		);

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
	 * Return the first available view.
	 *
	 * If no view can be found, an error message is displayed and
	 * execution is stopped.
	 *
	 * @since 1.0.0
	 */
	public function first(
		array $views,
		array|string $hierarchy = [],
		array|Collection $data = []
	): View {
		foreach ( $views as $name ) {
			$view = $this->make(
				$name,
				$hierarchy,
				$data
			);

			if ( $view->template() ) {
				return $view;
			}
		}

		Message::make( sprintf(
			'<p>Notice: View templates not found:</p> <ul>%s</ul>',
			implode( "\n", array_map(
				fn( $name ) => "<li><code>{$name}.php</code></li>",
				$views
			) )
		) )->dd();
	}

	/**
	 * Return any available view.
	 *
	 * Returns false if no view can be found.
	 *
	 * @since 1.0.0
	 */
	public function any(
		array $views,
		array|string $hierarchy = [],
		array|Collection $data = []
	): View|false {
		foreach ( $views as $name ) {
			$view = $this->make(
				$name,
				$hierarchy,
				$data
			);

			if ( $view->template() ) {
				return $view;
			}
		}

		return false;
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

	/**
	 * Push view data onto the current data stack.
	 *
	 * @since 1.0.0
	 */
	public function pushData( Collection $data ): void
	{
		$this->dataStack[] = $data;
	}

	/**
	 * Remove the current view data from the data stack.
	 *
	 * @since 1.0.0
	 */
	public function popData(): void
	{
		array_pop( $this->dataStack );
	}

	/**
	 * Get the current view data.
	 *
	 * @since 1.0.0
	 */
	public function data(): Collection
	{
		if ( ! $this->dataStack ) {
			return new Collection();
		}

		return $this->dataStack[
			array_key_last( $this->dataStack )
		];
	}

	/**
	 * Return a registered template tag object.
	 *
	 * @since 1.0.0
	 */
	public function tag(
		string $name,
		mixed ...$args
	): ?TemplateTag {
		return $this->tags->callback(
			$name,
			$this->data(),
			$args
		);
	}

	/**
	 * Allow registered template tags to be used as methods.
	 *
	 * @since 1.0.0
	 */
	public function __call(
		string $name,
		array $arguments
	): mixed {
		return $this->tag(
			$name,
			...$arguments
		);
	}
}