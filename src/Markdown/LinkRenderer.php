<?php
/**
 * Markdown link renderer.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Markdown;

use Novaris\Tools\Str;
use League\CommonMark\Extension\CommonMark\Node\Inline\{Image, Link};
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\{ChildNodeRendererInterface, NodeRendererInterface};
use League\CommonMark\Util\HtmlElement;

class LinkRenderer implements NodeRendererInterface {
	/**
	 * Renders the element.
	 *
	 * @since 1.0.0
	 */
	public function render( Node $node, ChildNodeRendererInterface $childRenderer )
	{
		$url = $node->getUrl();

		if ( Str::startsWith( $url, '/' ) ) {
			$url = Str::appendUri( url( $url ) );
		}

		$attr = $node->data['attributes'] ?? [];

		$attr['href'] = e( $url );

		if ( $title = $node->getTitle() ) {
			$attr['title'] = e( $title );
		}

		if ( 1 === count( $node->children() ) && $node->firstChild() instanceof Image ) {
			return ( new ImageRenderer( $node ) )->render( $node->firstChild(), $childRenderer );
		}


		$innerHtml = $childRenderer->renderNodes( $node->children() );

		return new HtmlElement( 'a', $attr, $innerHtml );
	}
}
