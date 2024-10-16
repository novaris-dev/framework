<?php
/**
 * App service provider.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core\Providers;

// Abstracts.
use Novaris\Core\ServiceProvider;

// Concretes.
use Novaris\Messenger\Message;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\{HtmlDumper, CliDumper};

class App extends ServiceProvider
{
	/**
	 * Register bindings.
	 *
	 * @since 1.0.0
	 */
        public function register(): void
	{
                // Sets the default timezone.
                date_default_timezone_set( $this->app['config']->get( 'app.timezone' ) );

		// Add messenger.
		$this->app->bind( Message::class );

		// Add aliases.
		$this->app->alias( Message::class, 'messenger.message' );

		// Set up variable dumper.
		$this->setVarDumper();
	}

	/**
	 * Sets the handler for Symfony's variable dumper. We are just making it
	 * look a little prettier with custom styles.
	 *
	 * @since 1.0.0
	 */
	private function setVarDumper(): void
	{
		VarDumper::setHandler( function( $var ) {
			$cloner      = new VarCloner();
			$html_dumper = new HtmlDumper();

			$html_dumper->setTheme( 'light' );

			$html_dumper->setStyles( [
				'default' => '
					box-sizing: border-box;
					position: relative;
					z-index: 99999;
					overflow: auto !important;
					word-break: break-all;
					word-wrap: normal;
					white-space: revert;
					margin: 2rem;
					max-width: 100%;
					padding: 2rem;
					font-family: \"Source Code Pro\", Monaco, Consolas, \"Andale Mono WT\", \"Andale Mono\", \"Lucida Console\", \"Lucida Sans Typewriter\", \"DejaVu Sans Mono\", \"Bitstream Vera Sans Mono\", \"Liberation Mono\", \"Nimbus Mono L\", \"Courier New\", Courier, monospace;
					font-size: 18px;
					line-height: 1.75;
					color: #334155;
					background: #f8fafc;
					border: 1px solid #e2e8f0;
					border-bottom-color: #cbd5e1;
					border-radius: 0;
					box-shadow: none;
				',
				'index'     => 'color: #60a5fa;',
		                'key'       => 'color: #16a34a;',
				'meta'      => 'color: #9333ea;',
				'note'      => 'color: #1d4ed8;',
				'num'       => 'color: #60a5fa;',
				'private'   => 'color: #64748b;',
				'protected' => 'color: #475569;',
				'ref'       => 'color: #3b82f6;',
		                'str'       => 'color: #16a34a;',
				'toggle'    => 'padding: 0 0.5rem'
			] );

			$dumper = PHP_SAPI === 'cli' ? new CliDumper() : $html_dumper;

			$dumper->dump( $cloner->cloneVar( $var ) );
		} );
        }
}
