/**
 * Returns the featured image for a content entry.
 *
 * @since 1.0.0
 *
 * @param  \Novaris\Contracts\Content\ContentEntry $entry Content entry.
 * @return \Novaris\Tools\Media|null                       Featured image or null.
 */
function featured_image(
	\Novaris\Contracts\Content\ContentEntry $entry
): \Novaris\Tools\Media|null
{
	$metadata = app( 'theme.metadata' );

	if ( ! $metadata->supports( 'featured-image' ) ) {
		return null;
	}

	return $entry->featuredImage();
}