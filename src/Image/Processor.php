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
	 * Processes an image using a registered image size.
	 *
	 * @since 1.0.0
	 */
	public function process(
		Media $media,
		string $size
	): ?Media {
		if ( ! $media->isValid() || ! $media->hasType( 'image' ) ) {
			return null;
		}

		$options = config(
			"app.supports.featured-image.sizes.{$size}"
		);

		if ( ! is_array( $options ) ) {
			return null;
		}

		$width  = (int) ( $options['width'] ?? 0 );
		$height = (int) ( $options['height'] ?? 0 );
		$crop   = (bool) ( $options['crop'] ?? false );

		if ( ! $width || ! $height ) {
			return null;
		}

		$pathinfo = pathinfo( $media->path() );

		$filename = sprintf(
			'%s-%dx%d.%s',
			$pathinfo['filename'],
			$width,
			$height,
			$pathinfo['extension']
		);

		$destination = $pathinfo['dirname']
			. DIRECTORY_SEPARATOR
			. $filename;

		if ( ! is_file( $destination ) ) {
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

		return new Media( $destination );
	}
}