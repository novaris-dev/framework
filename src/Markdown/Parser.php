<?php
/**
 * Markdown parser.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Markdown;

use Novaris\Contracts\Markdown\Parser as ParserContract;
use Novaris\Tools\Str;
use League\CommonMark\ConverterInterface;

class Parser implements ParserContract
{
	/**
	 * Stores content.
	 *
	 * @since 1.0.0
	 */
        protected string $content;

	/**
	 * Stores front matter.
	 *
	 * @since 1.0.0
	 */
        protected array $front_matter;

	/**
	 * Sets up object state.
	 *
	 * @since 1.0.0
	 */
        public function __construct( protected ConverterInterface $converter ) {}

	/**
	 * Converts Markdown to HTML.
	 *
	 * @since 1.0.0
	 */
        public function convert( string $content ): self
	{
                $this->front_matter = [];

		$match = Str::captureFrontMatter( $content );

                if ( $match ) {
			$this->front_matter = Str::yaml( $match );
			$content = Str::trimFrontMatter( $content );
                }

                $this->content = $this->converter->convert(
                        $content
                )->getContent();

                return $this;
        }

	/**
	 * Returns Markdown HTML.
	 *
	 * @since 1.0.0
	 */
        public function content(): string
	{
                return $this->content;
        }

	/**
	 * Returns YAML front matter.
	 *
	 * @since 1.0.0
	 */
        public function frontMatter(): array
	{
                return $this->front_matter;
        }
}
