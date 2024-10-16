<?php
/**
 * Markdown parser contract.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Contracts\Markdown;

interface Parser
{
	/**
	 * Converts Markdown to HTML.
	 *
	 * @since 1.0.0
	 */
        public function convert( string $content ): self;

	/**
	 * Returns Markdown HTML.
	 *
	 * @since 1.0.0
	 */
        public function content(): string;

	/**
	 * Returns YAML front matter.
	 *
	 * @since 1.0.0
	 */
        public function frontMatter(): array;
}