<?php
/**
 * Embed tests.
 *
 * With `supports.embeds`, a link on its own line in Markdown becomes an
 * embed: YouTube, Vimeo, or a player for a video or audio file.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

class EmbedTest extends TestCase
{
	/**
	 * Boots a site with `supports.embeds` set to `$embeds`.
	 */
	private function bootWith( mixed $embeds = true, array $files = [] ): void
	{
		$this->boot( $this->makeSite( $files, [ 'supports' => [ 'embeds' => $embeds ] ] ) );
	}

	/**
	 * Renders Markdown with the booted site's converter.
	 */
	private function render( string $markdown ): string
	{
		return trim( app( 'markdown' )->convert( $markdown )->content() );
	}

	public function testLinksStayLinksWhenEmbedsAreOff(): void
	{
		foreach ( [ null, false, 'yes' ] as $value ) {
			$this->bootWith( $value );

			$this->assertSame(
				'<p>https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>',
				$this->render( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ),
				var_export( $value, true )
			);
		}
	}

	public function testYoutubeLinksBecomeClickToLoadEmbeds(): void
	{
		$this->bootWith();

		$html = $this->render( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1m30s' );

		$this->assertStringStartsWith( '<figure class="embed embed--youtube embed--click-to-load">', $html );

		// The player only loads on click, from the privacy-friendly domain,
		// keeping the start time.
		$this->assertStringContainsString( 'data-embed-src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=90"', $html );
		$this->assertStringNotContainsString( '<iframe', $html );

		// Without JavaScript, the play button is a link to the video.
		$this->assertStringContainsString( 'href="https://www.youtube.com/watch?v=dQw4w9WgXcQ&amp;t=1m30s"', $html );
		$this->assertStringContainsString( 'src="https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg"', $html );
	}

	public function testYoutubeLinkForms(): void
	{
		$this->bootWith();

		$links = [
			'https://youtu.be/dQw4w9WgXcQ'                     => 'dQw4w9WgXcQ',
			'https://youtu.be/dQw4w9WgXcQ?t=42'                => 'dQw4w9WgXcQ?start=42',
			'https://m.youtube.com/watch?v=dQw4w9WgXcQ'        => 'dQw4w9WgXcQ',
			'https://www.youtube.com/shorts/aqz-KE-bpKQ'       => 'aqz-KE-bpKQ',
			'https://www.youtube.com/embed/dQw4w9WgXcQ'        => 'dQw4w9WgXcQ',
			'https://www.youtube.com/live/dQw4w9WgXcQ'         => 'dQw4w9WgXcQ',
			'https://youtube.com/watch?v=dQw4w9WgXcQ&start=7'  => 'dQw4w9WgXcQ?start=7',
		];

		foreach ( $links as $url => $embed ) {
			$this->assertStringContainsString( 'data-embed-src="https://www.youtube-nocookie.com/embed/' . $embed . '"', $this->render( $url ), $url );
		}

		// Not videos: no ID, a bad ID, or a channel page.
		foreach ( [ 'https://www.youtube.com/watch', 'https://youtu.be/short', 'https://www.youtube.com/@channel' ] as $url ) {
			$this->assertStringNotContainsString( 'embed', $this->render( $url ), $url );
		}
	}

	public function testVimeoLinks(): void
	{
		$this->bootWith();

		$links = [
			'https://vimeo.com/76979871'                       => '76979871?dnt=1',
			'https://vimeo.com/76979871/abc123def'             => '76979871?h=abc123def&amp;dnt=1',
			'https://vimeo.com/channels/staffpicks/76979871'   => '76979871?dnt=1',
			'https://player.vimeo.com/video/76979871'          => '76979871?dnt=1',
		];

		foreach ( $links as $url => $embed ) {
			$html = $this->render( $url );

			$this->assertStringContainsString( 'embed--vimeo', $html, $url );
			$this->assertStringContainsString( 'data-embed-src="https://player.vimeo.com/video/' . $embed . '"', $html, $url );
		}

		$this->assertStringNotContainsString( 'embed', $this->render( 'https://vimeo.com/about' ) );
	}

	public function testOptionsTurnOffClickToLoadAndPrivacy(): void
	{
		$this->bootWith( [ 'click_to_load' => false, 'privacy' => false ] );

		$html = $this->render( 'https://youtu.be/dQw4w9WgXcQ' );

		$this->assertStringContainsString( '<iframe class="embed__iframe" src="https://www.youtube.com/embed/dQw4w9WgXcQ"', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringNotContainsString( 'embed--click-to-load', $html );

		$this->assertStringContainsString( 'src="https://player.vimeo.com/video/76979871"', $this->render( 'https://vimeo.com/76979871' ) );
	}

	public function testMediaFilesGetPlayers(): void
	{
		$this->bootWith( true, [ 'user/media/clip.mp4' => 'not really a video' ] );

		$video = $this->render( '/user/media/clip.mp4' );
		$this->assertStringStartsWith( '<figure class="embed embed--video"><video class="embed__player" controls preload="metadata" playsinline>', $video );

		// Site files get their full URL.
		$this->assertStringContainsString( '<source src="' . static::URL . '/user/media/clip.mp4" type="video/mp4">', $video );

		$audio = $this->render( 'https://example.com/podcast/episode-1.mp3' );
		$this->assertStringContainsString( '<audio class="embed__player" controls preload="metadata"><source src="https://example.com/podcast/episode-1.mp3" type="audio/mpeg">', $audio );
	}

	public function testOnlyLinksOnTheirOwnLineAreEmbedded(): void
	{
		$this->bootWith();

		// A link in a sentence.
		$this->assertStringStartsWith( '<p>Watch https://youtu.be/dQw4w9WgXcQ', $this->render( 'Watch https://youtu.be/dQw4w9WgXcQ today.' ) );

		// A link with its own text.
		$this->assertSame(
			'<p><a href="https://youtu.be/dQw4w9WgXcQ">My video</a></p>',
			$this->render( '[My video](https://youtu.be/dQw4w9WgXcQ)' )
		);

		// Other links on their own line.
		$this->assertSame( '<p>https://example.com/page</p>', $this->render( 'https://example.com/page' ) );

		// Links to non-media files.
		$this->assertSame( '<p>/user/media/document.pdf</p>', $this->render( '/user/media/document.pdf' ) );
	}

	public function testAutolinksAndAngleBracketLinksAreEmbedded(): void
	{
		$this->bootWith( true, [
			'config/markdown.php' => "<?php\nreturn [ 'extensions' => [ League\\CommonMark\\Extension\\Autolink\\AutolinkExtension::class ] ];\n",
		] );

		$this->assertStringContainsString( 'embed--youtube', $this->render( 'https://youtu.be/dQw4w9WgXcQ' ) );
		$this->assertStringContainsString( 'embed--youtube', $this->render( '<https://youtu.be/dQw4w9WgXcQ>' ) );
		$this->assertStringContainsString( 'embed--vimeo', $this->render( '[https://vimeo.com/76979871](https://vimeo.com/76979871)' ) );

		// Autolinked links that aren't videos stay links.
		$this->assertSame(
			'<p><a href="https://example.com/page">https://example.com/page</a></p>',
			$this->render( 'https://example.com/page' )
		);
	}

	public function testUrlsAreEscaped(): void
	{
		$this->bootWith();

		$html = $this->render( 'https://example.com/a"onmouseover="alert(1).mp4' );

		$this->assertStringNotContainsString( '"onmouseover="', $html );
	}

	public function testLinkedImagesAreNotWrappedInParagraphs(): void
	{
		$this->boot( $this->makeSite() );

		// A linked image renders as a <figure>, which isn't allowed in a <p>.
		$html = $this->render( '[![Photo](/user/media/missing.jpg)](https://example.com)' );

		$this->assertStringNotContainsString( '<p>', $html );
		$this->assertStringStartsWith( '<figure', $html );
	}
}
