<?php
/**
 * Feed Writer interface.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Feed;

use Novaris\Contracts\Content\ContentQuery;

interface FeedWriter
{
	/**
	 * Returns the Feed title.
	 *
	 * @since 1.0.0
	 */
	public function title(): string;

	/**
	 * Returns the Feed webpage URL.
	 *
	 * @since 1.0.0
	 */
	public function url(): string;

	/**
	 * Returns the Feed feed URL.
	 *
	 * @since 1.0.0
	 */
	public function feedUrl(): string;

	/**
	 * Returns the Feed description.
	 *
	 * @since 1.0.0
	 */
	public function description(): string;

	/**
	 * Returns the Feed language.
	 *
	 * @since 1.0.0
	 */
	public function language(): string;

	/**
	 * Returns the Feed TTL.
	 *
	 * @since 1.0.0
	 */
	public function copyright(): ?string;

	/**
	 * Returns the feed published datetime.
	 *
	 * @since 1.0.0
	 */
	public function published(): ?string;

	/**
	 * Returns the feed updated datetime.
	 *
	 * @since 1.0.0
	 */
	public function updated(): ?string;

	/**
	 * Returns the Feed TTL.
	 *
	 * @since 1.0.0
	 */
	public function ttl(): int;

	/**
	 * Returns the collection.
	 *
	 * @since 1.0.0
	 */
	public function collection(): ContentQuery;
}