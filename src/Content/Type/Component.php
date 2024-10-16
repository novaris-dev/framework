<?php
/**
 * Content types component.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Content\Type;

use Novaris\Contracts\Bootable;
use Novaris\Contracts\Content\ContentTypes;

class Component implements Bootable
{
	/**
	 * Sets up object state.
	 *
	 * @since 1.0.0
	 */
        public function __construct( protected ContentTypes $registry, protected array $types ) {}

	/**
	 * Registers content types on boot.
	 *
	 * @since 1.0.0
	 */
        public function boot(): void {

            // Registers user-configured content types.
            foreach ( $this->types as $name => $options ) {
                $this->registry->add( $name, $options );
            }

            // Registers the virtual content type.
            $this->registry->add( 'virtual', [
                'public'  => false,
                'routing' => false
            ] );
	}
}