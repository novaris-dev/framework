<?php
/**
 * View template.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template;

// Contracts.
use Stringable;
use Novaris\Contracts\Template\TemplateView;

// Concretes.
use Novaris\App;
use Novaris\Tools\Collection;

class View implements TemplateView, Stringable
{
	/**
	 * An collection of data that is passed into the view template.
	 *
	 * @since  1.0.0
	 */
	protected Collection $data;

	/**
	 * The template filename.
	 *
	 * @since  1.0.0
	 */
	protected ?string $template = null;

	/**
	 * Sets up the view properties.
	 *
	 * @since  1.0.0
	 */
	public function __construct( protected string $name, array|Collection $data = [] )
	{
		$this->name = str_replace( '/', '.', $this->name );

		if ( ! $data instanceof Collection ) {
			$data = new Collection( (array) $data );
		}

		$this->data = $data;
	}

	/**
	 * Returns the located template.
	 *
	 * @since 1.0.0
	 */
	public function template(): string
	{
		if ( is_null( $this->template ) ) {
			$filename       = str_replace( '.', '/', $this->name );
			$this->template = view_path( "{$filename}.php" );
		}

		return $this->template;
	}

	/**
	 * Sets the view data.
	 *
	 * @since 1.0.0
	 */
	public function setData( Collection $data ): void
	{
		$this->data = $data;
	}

	/**
	 * Gets the view data.
	 *
	 * @since 1.0.0
	 */
	public function getData(): Collection
	{
		return $this->data;
	}

	/**
	 * Displays the view.
	 *
	 * @since 1.0.0
	 */
	public function display(): void
	{
		echo $this->render();
	}

	/**
	 * Returns the view.
	 *
	 * @since 1.0.0
	 */
	public function render(): string
	{
		if ( ! $this->template() ) {
			return '';
		}

		// Extract the data into individual variables. Each of
		// these variables will be available in the template.
		extract( $this->data->all() );

		// Make `$data` and `$view` variables available to templates.
		$data = $this->data;
		$view = $this;

		ob_start();
		include $this->template();
		return ob_get_clean();
	}

	/**
	 * When attempting to use the object as a string, return the template
	 * output.
	 *
	 * @since 1.0.0
	 */
	public function __toString(): string
	{
		return $this->render();
	}
}
