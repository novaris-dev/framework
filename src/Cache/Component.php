<?php
/**
 * Cache component.
 *
 * Bootstraps the cache component, acting as a bridge to the cache registry.
 * On booting, it sets up the default and user-configured drivers and stores.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Cache;

// Contracts.
use Novaris\Contracts\Bootable;
use Novaris\Contracts\Cache\Registry;

// Classes.
use Novaris\Config;

class Component implements Bootable
{
	/**
	 * Sets up object state.
	 *
	 * @since  1.0.0
	 */
	public function __construct(
		protected Registry $registry,
		protected array $drivers,
		protected array $stores
	) {}

	/**
	 * Bootstraps the component, setting up cache drivers and stores.
	 *
	 * @since  1.0.0
	 */
	public function boot(): void
	{
		// Add drivers to the cache registry.
		foreach ( $this->drivers as $name => $driver ) {
			$this->registry->addDriver( $name, $driver );
		}

		// Add stores to the cache registry.
		foreach ( $this->stores as $name => $options ) {
			$this->registry->addStore( $name, $options );
		}
	}
}