<?php
/**
 * Font.
 *
 * Creates a font object.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/novaris-dev/framework
 */

namespace Novaris\Font;

use JsonSerializable;

/**
 * Font class.
 *
 * @since 1.0.0
 */
class Font implements JsonSerializable
{
	/**
	 * Font ID.
	 *
	 * @since 1.0.0
	 */
	protected string $id;

	/**
	 * Font family.
	 *
	 * @since 1.0.0
	 */
	protected string $family = '';

	/**
	 * Font stack.
	 *
	 * @since 1.0.0
	 */
	protected string $stack = '';

	/**
	 * Google Font family.
	 *
	 * @since 1.0.0
	 */
	protected ?string $google = null;

	/**
	 * Font styles.
	 *
	 * @since 1.0.0
	 */
	protected array $styles = [];

	/**
	 * Set up the object properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $id, array $options = [] )
	{
		foreach ( array_keys( get_object_vars( $this ) ) as $key ) {
			if ( array_key_exists( $key, $options ) ) {
				$this->$key = $options[ $key ];
			}
		}

		$this->id = $id;
	}

	/**
	 * Returns a JSON-ready representation of the font.
	 *
	 * @since 1.0.0
	 */
	public function jsonSerialize(): array
	{
		return [
			'id'     => $this->id(),
			'family' => $this->family(),
			'stack'  => $this->stack(),
			'google' => $this->google(),
			'styles' => $this->styles()
		];
	}

	/**
	 * Returns the font ID.
	 *
	 * @since 1.0.0
	 */
	public function id(): string
	{
		return $this->id;
	}

	/**
	 * Returns the font family.
	 *
	 * @since 1.0.0
	 */
	public function family(): string
	{
		return $this->family;
	}

	/**
	 * Returns the font stack.
	 *
	 * @since 1.0.0
	 */
	public function stack(): string
	{
		return $this->stack;
	}

	/**
	 * Returns the Google Font family.
	 *
	 * @since 1.0.0
	 */
	public function google(): ?string
	{
		return $this->google;
	}

	/**
	 * Returns the font styles.
	 *
	 * @since 1.0.0
	 */
	public function styles(): array
	{
		return $this->styles;
	}

	/**
	 * Determines whether the font is a Google Font.
	 *
	 * @since 1.0.0
	 */
	public function isGoogle(): bool
	{
		return null !== $this->google;
	}
}