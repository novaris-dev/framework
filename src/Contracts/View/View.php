<?php
/**
 * View interface.
 *
 * Defines the contract for locating and rendering view templates.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\View;

interface View
{
	/**
	 * Get the view name.
	 *
	 * @since 1.0.0
	 */
	public function name(): string;

	/**
	 * Get the view hierarchy.
	 *
	 * @since 1.0.0
	 */
	public function hierarchy(): array;

	/**
	 * Locate the first available view template.
	 *
	 * @since 1.0.0
	 */
	public function locate(): ?string;

	/**
	 * Get the located template.
	 *
	 * @since 1.0.0
	 */
	public function template(): ?string;

	/**
	 * Display the view.
	 *
	 * @since 1.0.0
	 */
	public function display(): void;

	/**
	 * Render the view.
	 *
	 * @since 1.0.0
	 */
	public function render(): string;
}