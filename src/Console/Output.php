<?php
/**
 * Console output.
 *
 * Handles formatted output for Novaris command-line commands.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Console;

class Output
{
	/**
	 * Write a line to the console.
	 *
	 * @since 1.0.0
	 */
	public function line( string $message = '' ): void
	{
		echo $message . PHP_EOL;
	}

	/**
	 * Write an informational message.
	 *
	 * @since 1.0.0
	 */
	public function info( string $message ): void
	{
		$this->line( $message );
	}

	/**
	 * Write a success message.
	 *
	 * @since 1.0.0
	 */
	public function success( string $message ): void
	{
		$this->line(
			$this->color( 'Success:', '32' ) . " {$message}"
		);
	}

	/**
	 * Write a warning message.
	 *
	 * @since 1.0.0
	 */
	public function warning( string $message ): void
	{
		$this->line(
			$this->color( 'Warning:', '33' ) . " {$message}"
		);
	}

	/**
	 * Write an error message.
	 *
	 * @since 1.0.0
	 */
	public function error( string $message ): void
	{
		$this->line(
			$this->color( 'Error:', '31' ) . " {$message}"
		);
	}

	/**
	 * Color console output when supported.
	 *
	 * @since 1.0.0
	 */
	protected function color( string $message, string $color ): string
	{
		if ( ! $this->supportsColor() ) {
			return $message;
		}

		return "\033[{$color}m{$message}\033[0m";
	}

	/**
	 * Determine whether the terminal supports colored output.
	 *
	 * @since 1.0.0
	 */
	protected function supportsColor(): bool
	{
		if ( DIRECTORY_SEPARATOR === '\\' ) {
			return function_exists( 'sapi_windows_vt100_support' )
				&& sapi_windows_vt100_support( STDOUT );
		}

		return function_exists( 'posix_isatty' )
			? posix_isatty( STDOUT )
			: function_exists( 'stream_isatty' )
				&& stream_isatty( STDOUT );
	}
}