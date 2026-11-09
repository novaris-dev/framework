<?php
/**
 * Image size file tests.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

class ImageSizeTest extends TestCase
{
	/**
	 * Boots a site with a 400 × 200 thumbnail and two 100 × 100 sizes, one
	 * cropped and one not.
	 */
	private function bootSite(): string
	{
		if ( ! extension_loaded( 'gd' ) ) {
			$this->markTestSkipped( 'Needs the GD extension to create a test image.' );
		}

		$site = $this->makeSite( [
			'user/content/_posts/2025-04-01.photo.md' => $this->markdown( [
				'title'     => 'Photo',
				'published' => '2025-04-01',
				'thumbnail' => '/user/media/wide.png',
			] ),
		], [
			'supports' => [
				'featured-image' => [
					'sizes' => [
						'square' => [ 'width' => 100, 'height' => 100, 'crop' => true ],
						'fit'    => [ 'width' => 100, 'height' => 100, 'crop' => false ],
						'big'    => [ 'width' => 800, 'height' => 400, 'crop' => true ],
					],
				],
			],
		] );

		mkdir( "{$site}/user/media" );
		imagepng( imagecreatetruecolor( 400, 200 ), "{$site}/user/media/wide.png" );

		$this->boot( $site );

		return $site;
	}

	private function thumbnail( string $size ): ?\Novaris\Tools\Media
	{
		return app( 'content.query' )->make( [ 'path' => '_posts', 'slug' => 'photo' ] )->single()->thumbnail( $size );
	}

	public function testCroppedAndUncroppedSizesDoNotShareAFile(): void
	{
		$site = $this->bootSite();

		$square = $this->thumbnail( 'square' );
		$fit    = $this->thumbnail( 'fit' );

		$this->assertSame( "{$site}/user/media/wide-100x100.png", $square->path() );
		$this->assertSame( "{$site}/user/media/wide-100x100-fit.png", $fit->path() );

		// Cropped to a square; fitted inside the box, keeping its shape.
		$this->assertSame( [ 100, 100 ], [ $square->width(), $square->height() ] );
		$this->assertSame( [ 100, 50 ], [ $fit->width(), $fit->height() ] );

		// Asking again, in the other order, still gives each its own image.
		$this->assertSame( [ 100, 50 ], [ $this->thumbnail( 'fit' )->width(), $this->thumbnail( 'fit' )->height() ] );
		$this->assertSame( [ 100, 100 ], [ $this->thumbnail( 'square' )->width(), $this->thumbnail( 'square' )->height() ] );
	}

	public function testEnlargedFilesFromOlderVersionsAreMadeAgain(): void
	{
		$site = $this->bootSite();

		// An enlarged file, as versions before 1.1.4 made for cropped sizes.
		imagepng( imagecreatetruecolor( 800, 400 ), "{$site}/user/media/wide-800x400.png" );

		$big = $this->thumbnail( 'big' );

		$this->assertSame( [ 400, 200 ], [ $big->width(), $big->height() ] );
		$this->assertSame( [ 400, 200 ], array_slice( getimagesize( "{$site}/user/media/wide-800x400.png" ), 0, 2 ) );
	}

	public function testFilesThatAreNotEnlargedAreReused(): void
	{
		$site = $this->bootSite();

		$this->thumbnail( 'square' );
		touch( "{$site}/user/media/wide-100x100.png", 1000000000 );
		clearstatcache();

		$this->thumbnail( 'square' );
		clearstatcache();

		$this->assertSame( 1000000000, filemtime( "{$site}/user/media/wide-100x100.png" ) );
	}
}
