<?php
/**
 * Template component class.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template;

// Contracts.
use Novaris\Contracts\Bootable;
use Novaris\Contracts\Template\TemplateTags;

class Component implements Bootable
{
	/**
	 * Sets up the object state.
	 *
	 * @since 1.0.0
	 */
	public function __construct( protected TemplateTags $registry, protected array $tags
	) {}

	/**
	 * Bootstraps the component.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void
	{
		foreach ( $this->tags as $name => $callback ) {
			$this->registry->add( $name, $callback );
		}
	}
}
