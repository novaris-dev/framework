<?php
/**
 * JSON file store implementation.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Cache\Drivers;

use Novaris\Tools\Str;

class JsonFile extends File
{
	/**
	 * File extenstion for the store's files without the preceding dot.
	 *
	 * @since  1.0.0
	 */
	protected string $extension = 'json';

	/**
	 * Returns data from a store by cache key.
	 *
	 * @since  1.0.0
	 */
	public function get( string $key ): mixed
	{
		if ( $this->hasData( $key ) ) {
			return $this->getData( $key );
		}

		if ( $data = $this->getJsonFileContents( $key ) ) {

			if ( $this->hasExpired( $data ) ) {
				$this->forget( $key );
				return null;
			}

			$this->setData( $key, $data );
		}

		return $this->data[$key]['data'] ?? null;
	}

	/**
	 * Writes new data or replaces existing data by cache key.
	 *
	 * @since  1.0.0
	 */
	public function put( string $key, mixed $data, int $seconds = 0 ): bool
	{
		$put = $this->putJsonFileContents( $key, $data, $seconds );

		if ( true === $put ) {
			$this->setData( $key, $data );
		}

		return $put;
	}

	/**
	 * Gets the cache file contents by key and runs it through `json_decode()`.
	 *
	 * @since  1.0.0
	 */
	protected function getJsonFileContents( string $key ): array|false
	{
		if ( ! $this->fileExists( $key ) ) {
			return false;
		}

		$contents = file_get_contents( $this->filepath( $key ) );

		$decoded = $contents ? json_decode( $contents, true ) : false;

		return $decoded ?: false;
	}

	/**
	 * Encodes an array of data to JSON and writes it to the file path.
	 *
	 * @since  1.0.0
	 */
	protected function putJsonFileContents( string $key, array $data, int $seconds ): bool
	{
		$data = json_encode( [
			'meta' => [
				'expires' => $this->availableAt( $seconds ),
				'created' => $this->createdAt()
			],
			'data' => $data
		], JSON_PRETTY_PRINT );

		$put = file_put_contents( $this->filepath( $key ), $data );

		// `file_put_contents()` returns `int|false`.
		return false !== $put;
	}
}