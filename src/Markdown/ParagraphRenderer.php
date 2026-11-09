<?php
/**
 * Markdown paragraph renderer.
 *
 * Renders paragraphs, except that an image (or linked image) on its own
 * isn't wrapped in `<p>`, because it renders as a `<figure>`, and a link on
 * its own line becomes an embed when embeds are turned on (see `Embed`).
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\{Image, Link};
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\{ChildNodeRendererInterface, NodeRendererInterface};
use League\CommonMark\Util\HtmlElement;

class ParagraphRenderer implements NodeRendererInterface
{
	public function render( Node $node, ChildNodeRendererInterface $childRenderer )
	{
		if ( Embed::enabled() && ( $url = $this->standaloneUrl( $node ) ) && ( $embed = Embed::html( $url ) ) ) {
			return $embed;
		}

		$innerHtml = $childRenderer->renderNodes( $node->children() );

		if ( $node->parent() instanceof ListItem && 1 === count( $node->parent()->children() ) ) {
			return $innerHtml;
		}

		// Don't wrap images, or linked images, with <p> tags: they render
		// as a <figure>, which isn't allowed inside a <p>.
		if ( 1 === count( $node->children() ) ) {
			$child = $node->firstChild();

			if ( $child instanceof Image ) {
				return $innerHtml;
			}

			if ( $child instanceof Link && 1 === count( $child->children() ) && $child->firstChild() instanceof Image ) {
				return $innerHtml;
			}
		}

		return new HtmlElement(
			'p',
			$node->data['attributes'] ?? [],
			$innerHtml
		);
	}

	/**
	 * Returns the URL when the paragraph is nothing but a link: a bare URL,
	 * an autolink (`<https://…>`), or a Markdown link whose text is its URL.
	 * Otherwise returns `null`. Paragraphs with attributes are left alone.
	 *
	 * @since 1.1.3
	 */
	protected function standaloneUrl( Node $node ): ?string
	{
		if ( ! empty( $node->data['attributes'] ) || 1 !== count( $node->children() ) ) {
			return null;
		}

		$child = $node->firstChild();

		// A bare URL, when the Autolink extension isn't on.
		if ( $child instanceof Text ) {
			$text = trim( $child->getLiteral() );

			return preg_match( '#^(https?://|/)\S+$#i', $text ) ? $text : null;
		}

		// A link whose only content is its own URL.
		if ( $child instanceof Link && 1 === count( $child->children() ) && $child->firstChild() instanceof Text ) {
			$url  = $child->getUrl();
			$text = trim( $child->firstChild()->getLiteral() );

			// Autolinks may drop the scheme from the text (`www.…`).
			if ( $text === $url || 'http://' . $text === $url || 'https://' . $text === $url ) {
				return $url;
			}
		}

		return null;
	}
}
