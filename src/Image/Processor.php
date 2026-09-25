<?php
/**
 * Image processor.
 *
 * Handles image resizing and cropping using Intervention Image.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Image;

use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Novaris\Tools\Media;

class Processor
{
	/**
	 * Image manager.
	 *
	 * @since 1.0.0
	 */
	protected ImageManager $manager;

	/**
	 * Creates the image processor.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->manager = new ImageManager(
			new Driver()
		);
	}

	/**
	 * Processes an image.
	 *
	 * @since 1.0.0
	 */
	public function process(
		Media $media,
		string $destination,
		int $width,
		int $height,
		bool $crop = false
	): void {
		if ( ! $media->isValid() || ! $media->hasType( 'image' ) ) {
			return;
		}

		$image = $this->manager->read(
			$media->path()
		);

		if ( $crop ) {
			$image->cover( $width, $height );
		} else {
			$image->scaleDown(
				width: $width,
				height: $height
			);
		}

		$image->save( $destination );
	}
}