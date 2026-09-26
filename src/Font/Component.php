<?php
/**
 * Font component.
 *
 * Manages the font component.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\Font;

use Novaris\Contracts\Bootable;

/**
 * Font component class.
 *
 * @since 1.0.0
 */
class Component implements Bootable
{
	/**
	 * Stores the fonts collection.
	 *
	 * @since 1.0.0
	 */
	protected Fonts $fonts;

	/**
	 * Stores the font catalog.
	 *
	 * @since 1.0.0
	 */
	protected Catalog $catalog;

	/**
	 * Stores the font configuration.
	 *
	 * @since 1.0.0
	 */
	protected array $config;

	/**
	 * Creates the component object.
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		Fonts $fonts,
		Catalog $catalog,
		array $config = []
	) {
		$this->fonts   = $fonts;
		$this->catalog = $catalog;
		$this->config  = $config;
	}

	/**
	 * Bootstraps the component.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void
	{
		foreach ( $this->config as $id ) {
			$options = $this->catalog->get( $id );

			if ( ! $options ) {
				continue;
			}

			$this->fonts->add( $id, $options );
		}
	}
}