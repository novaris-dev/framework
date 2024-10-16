<?php
/**
 * Markdown service provider.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core\Providers;

use Novaris\Contracts\Markdown\Parser as ParserContract;

use Novaris\Core\ServiceProvider;
use Novaris\Markdown\{Parser, ImageRenderer, LinkRenderer, ParagraphRenderer};

use League\CommonMark\{ConverterInterface, MarkdownConverter};
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\Node\Inline\{Image, Link};
use League\CommonMark\Node\Block\Paragraph;

class Markdown extends ServiceProvider
{
	/**
	 * Register bindings.
	 *
	 * @since 1.0.0
	 */
        public function register(): void
	{
		// Sets up the Markdown converter and environment.
		$this->app->singleton( ConverterInterface::class, function( $app ) {

			// Gets the user Markdown config.
                        $markdown = $app->get( 'config' )->get( 'markdown' );

                        // Configure the Environment.
                        $environment = new Environment( $markdown['config'] );

			// Loops through user-added extensions and adds them.
                        foreach ( $markdown['extensions'] as $extension ) {
                                $environment->addExtension( new $extension() );
                        }

			// Loops through user-added inline parsers and adds them.
			foreach ( $markdown['inline_parsers'] as $parser ) {
				$environment->addInlineParser( new $parser() );
			}

			// Add default renderers.
                        $renderers = [
                                Image::class     => ImageRenderer::class,
                                Link::class      => LinkRenderer::class,
                                Paragraph::class => ParagraphRenderer::class
                        ];

			// Loops through renderers and adds them.
                        foreach ( $renderers as $node => $renderer ) {
                                $environment->addRenderer( $node, new $renderer() );
                        }

			// Return Markdown converter instance.
                        return new MarkdownConverter( $environment );
                } );

		// Binds a Markdown wrapper class for accessing the converter.
		$this->app->bind( ParserContract::class, function( $app ) {
			return new Parser( $app->make( ConverterInterface::class ) );
		} );

		$this->app->alias( ConverterInterface::class, 'markdown.converter' );
		$this->app->alias( ParserContract::class,     'markdown'           );
        }
}
