<?php
/**
 * Document title class.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template\Tag;

// Contracts.
use Novaris\Contracts\{Displayable, Renderable};

// Concretes.
use Novaris\Core\Proxies\{App, Config};
use Novaris\Tools\Str;

class DocumentTitle implements Displayable, Renderable
{
	/**
	 * Stores the built document title.
	 *
	 * @since 1.0.0
	 */
	protected string $doctitle = '';

	/**
	 * View title.
	 *
	 * @since 1.0.0
	 */
	protected string $view_title = '';

	/**
	 * Page number for paged views.
	 *
	 * @since 1.0.0
	 */
	protected int $page = 1;

	/**
	 * Separator string between doctitle items
	 *
	 * @since  1.0.0
	 */
	protected string $sep = '&mdash;';

	/**
	 * Sets up the object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $title = '', array $options = [] )
	{
		$this->view_title = $title;

		if ( isset( $options['page'] ) ) {
			$this->page = abs( intval( $options['page'] ) );
		}
	}

	/**
	 * Returns the doctitle between `<title>` tags.
	 *
	 * @since 1.0.0
	 */
	public function toHtml(): string
	{
		return $this->render();
	}

	/**
	 * Displays the doctitle.
	 *
	 * @since 1.0.0
	 */
	public function display(): void
	{
		echo $this->render();
	}

	/**
	 * Returns the doctitle.
	 *
	 * @since 1.0.0
	 */
	public function render(): string
	{
		if ( ! $this->doctitle ) {
			$this->doctitle = $this->build();
		}

		return sprintf( '<title>%s</title>', $this->doctitle );
	}

	/**
	 * Builds the doctitle.
	 *
	 * @since 1.0.0
	 */
	protected function build(): string
	{
		$app_title   = Config::get( 'app.title'   );
		$app_tagline = Config::get( 'app.tagline' );
		$paged       = 2 <= $this->page;
		$items       = [];

		$items['title'] = $this->view_title ? \e( $this->view_title ) : \e( $app_title );

		if ( $paged ) {
			$items['title'] .= sprintf( ': Page %d', intval( $this->page ) );
		}

		if ( $this->view_title ) {
			$items['app_title'] = \e( $app_title );
		}

		if ( ! $this->view_title && ! $paged ) {
			$items['app_tagline'] = \e( $app_tagline );
		}

		return implode( " {$this->sep} ", array_filter( $items ) );
	}
}
