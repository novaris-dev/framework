<?php
/**
 * Message class.
 *
 * This is a built-in messaging system for the framework to display notices,
 * errors, and other necessary information to the user.  It should not be
 * considered a part of the public API at this time and is for internal use only.
 * In the long term, the plan is to scale this out for any type of system
 * messages, including custom ones.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Messenger;

use Novaris\Tools\Str;

class Message
{
	/**
	 * Message to output.
	 *
	 * @since 1.0.0
	 */
	protected string $message = '';

	/**
	 * Type of message.  Appended as a class of `.novaris-message--{$type}`.
	 * The default supported types are `note`, `success`, `warning`, and
	 * `error`.
	 *
	 * @since 1.0.0
	 */
	protected string $type = 'note';

	/**
	 * Makes a new message.
	 *
	 * @since 1.0.0
	 */
	public function make( string $message, string $type = 'note' ): self
	{
		$message = trim( $message );
		$message = ! Str::startsWith( $message, '<' ) ? "<p>{$message}</p>" : $message;

		$this->message = $message;
		$this->type    = $type;

		return $this;
	}

	/**
	 * Returns the message and styles as HTML.
	 *
	 * @since 1.0.0
	 */
	public function render(): string
	{
		$message = sprintf(
			'<div class="novaris-message novaris-message--%s">%s</div>',
			e( $this->type ?: 'note' ),
			$this->message
		);

	 	$styles = str_replace( [ "\t", "\n", "\r", "\s\s", "  " ], '', $this->styles() );

		return $message . $styles;
	}

	/**
	 * Displays the message and styles HTML.
	 *
	 * @since 1.0.0
	 */
	public function display(): void
	{
		echo $this->render();
	}

	/**
	 * Alias for `display()`.
	 *
	 * @since 1.0.0
	 */
	public function dump(): void
	{
		echo $this->display();
	}

	/**
	 * Dumps the HTML and dies.
	 *
	 * @since 1.0.0
	 */
	public function dd(): void
	{
		$this->dump();
		die();
	}

	/**
	 * Returns the CSS stylesheet.
	 *
	 * @since 1.0.0
	 */
	protected function styles(): string
	{
		return '<style>
		.novaris-message {
			--novaris-message-spacing: 2rem;
			--novaris-message-color: #484a4c;
			--novaris-message-color-accent: #484a4c;
			--novaris-message-color-bg: #f4f9ff;
			--novaris-message-color-shadow: #e9f3f8;
			--novaris-message-color-shadow-text: #e9f3f8;

			clear:         both;
			position:      relative;
			box-sizing:    border-box;
			z-index:       999;

			width:         1024px;
			max-width:     100%;
			box-sizing:    border-box;
			margin:        var( --novaris-message-spacing ) auto;
			padding:       var( --novaris-message-spacing );

			font-family:   \"Source Code Pro\", Monaco, Consolas, \"Andale Mono WT\", \"Andale Mono\", \"Lucida Console\", \"Lucida Sans Typewriter\", \"DejaVu Sans Mono\", \"Bitstream Vera Sans Mono\", \"Liberation Mono\", \"Nimbus Mono L\", \"Courier New\", Courier, monospace;
			font-size:     18px;
			line-height:   1.75;
			color:         var( --novaris-message-color );
			text-shadow:   0 1px var( --novaris-message-color-shadow-text );
			background:    var( --novaris-message-color-bg );
			box-shadow:    inset 1px 1px 10px var( --novaris-message-color-shadow );
			border-radius: 6px;
			border-left:   6px solid var( --novaris-message-color-accent );
		}
		.novaris-message--note {
			--novaris-message-color-accent: #2282bb;
			--novaris-message-color: #2282bb;
		}
		.novaris-message--success {
			--novaris-message-color-accent: #338d00;
		}
		.novaris-message--error {
			--novaris-message-color-accent: #e23140;
		}
		.novaris-message--warning {
			--novaris-message-color-accent: #d59401;
		}
		.novaris-message > * {
			margin-top: 0;
			margin-bottom: 0;
		}
		.novaris-message > * + * {
			margin-top: var( --novaris-message-spacing );
			margin-bottom: 0;
		}
		.novaris-message :first-child {
			margin-top: 0;
		}
		.novaris-message ul {
			list-style-type: circle;
		}
		.novaris-message code {
			font: inherit;
			color: #484a4c;
			padding: 0.125em 0.25em;
			background: #e6eef9;
		}
		</style>';
	}
}
