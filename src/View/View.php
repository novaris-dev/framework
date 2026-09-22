<?php
/**
 * View.
 *
 * Handles locating and rendering view templates.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\View;

use Novaris\Contracts\View\View as ViewContract;
use Novaris\Tools\Collection;

class View implements ViewContract
{
	/**
	 * View engine.
	 *
	 * @since 1.0.0
	 */
	protected Engine $engine;

	/**
	 * View name.
	 *
	 * @since 1.0.0
	 */
	protected string $name;

	/**
	 * View hierarchy.
	 *
	 * @since 1.0.0
	 */
	protected array $hierarchy = [];

	/**
	 * View data.
	 *
	 * @since 1.0.0
	 */
	protected Collection $data;

	/**
	 * Located template.
	 *
	 * @since 1.0.0
	 */
	protected ?string $template = null;

	/**
	 * Create a new view.
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		Engine $engine,
		string $name,
		array|string $hierarchy = [],
		array|Collection $data = []
	) {
		$this->engine    = $engine;
		$this->name      = $name;
		$this->hierarchy = (array) $hierarchy;
		$this->data      = $data instanceof Collection
			? $data
			: new Collection( $data );
	}

	/**
	 * Get the view name.
	 *
	 * @since 1.0.0
	 */
	public function name(): string
	{
		return $this->name;
	}

	/**
	 * Get the view hierarchy slugs.
	 *
	 * @since 1.0.0
	 */
	public function slugs(): array
	{
		return $this->hierarchy;
	}

	/**
	 * Get the view data.
	 *
	 * @since 1.0.0
	 */
	public function data(): Collection
	{
		return $this->data;
	}

	/**
	 * Get the view hierarchy.
	 *
	 * @since 1.0.0
	 */
	public function hierarchy(): array
	{
		if ( str_contains( $this->name, '/' ) ) {
			return [ "{$this->name}.php" ];
		}

		$templates = [];

		foreach ( $this->hierarchy as $template ) {
			$templates[] = "{$this->name}/{$template}.php";
		}

		$default = "{$this->name}/default.php";

		if ( ! in_array( $default, $templates, true ) ) {
			$templates[] = $default;
		}

		$templates[] = "{$this->name}.php";

		return $templates;
	}

	/**
	 * Locate the first available view template.
	 *
	 * Active theme views take precedence over parent theme views, which take
	 * precedence over framework views.
	 *
	 * @since 1.0.0
	 */
	public function locate(): ?string
	{
		$parentTheme = parent_theme_path();

		foreach ( $this->hierarchy() as $template ) {
			$themeTemplate = theme_path(
				"public/views/{$template}"
			);

			if ( is_file( $themeTemplate ) ) {
				return $themeTemplate;
			}

			if ( $parentTheme ) {
				$parentTemplate = parent_theme_path(
					"public/views/{$template}"
				);

				if ( is_file( $parentTemplate ) ) {
					return $parentTemplate;
				}
			}

			$frameworkTemplate = view_path( $template );

			if ( is_file( $frameworkTemplate ) ) {
				return $frameworkTemplate;
			}
		}

		return null;
	}

	/**
	 * Get the located template.
	 *
	 * @since 1.0.0
	 */
	public function template(): ?string
	{
		if ( null === $this->template ) {
			$this->template = $this->locate();
		}

		return $this->template;
	}

	/**
	 * Display the view.
	 *
	 * @since 1.0.0
	 */
	public function display(): void
	{
		$template = $this->template();

		if ( ! $template ) {
			return;
		}

		extract(
			$this->data->all(),
			EXTR_SKIP
		);

		$data      = $this->data;
		$view      = $this;
		$engine    = $this->engine;
		$hierarchy = $this->slugs();

		$this->engine->pushData( $this->data );

		try {
			include $template;
		} finally {
			$this->engine->popData();
		}
	}

	/**
	 * Render the view.
	 *
	 * @since 1.0.0
	 */
	public function render(): string
	{
		ob_start();

		$this->display();

		return ob_get_clean();
	}

	/**
	 * Render the view when converted to a string.
	 *
	 * @since 1.0.0
	 */
	public function __toString(): string
	{
		return $this->render();
	}
}
