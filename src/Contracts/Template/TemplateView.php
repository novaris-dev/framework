<?php
/**
 * Template view interface.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Template;

// Concretes.
use Novaris\Tools\Collection;

interface TemplateView
{
	/**
	 * Sets the view data.
	 *
	 * @since 1.0.0
	 */
	public function setData( Collection $data ): void;

	/**
	 * Gets the view data.
	 *
	 * @since 1.0.0
	 */
	public function getData(): Collection;

	/**
	 * Returns the located template.
	 *
	 * @since 1.0.0
	 */
	public function template(): string;

	/**
	 * Displays the view.
	 *
	 * @since 1.0.0
	 */
	public function display(): void;

	/**
	 * Returns the view.
	 *
	 * @since 1.0.0
	 */
	public function render(): string;
}