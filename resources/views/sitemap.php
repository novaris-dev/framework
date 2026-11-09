<?php
/**
 * Default sitemap for one content type.
 *
 * Used when the theme has no `resources/views/sitemap.php`. Hidden entries
 * are left out.
 *
 * @var \Novaris\Contracts\Content\ContentQuery $collection
 */

$xml = fn( $value ) => htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ( $collection as $entry ) : ?>
<?php if ( ! $entry->isPublic() || ! $entry->url() ) { continue; } ?>
<?php $modified = $entry->updated( DATE_ATOM ) ?: $entry->published( DATE_ATOM ); ?>
	<url>
		<loc><?= $xml( $entry->url() ) ?></loc>
<?php if ( $modified ) : ?>
		<lastmod><?= $xml( $modified ) ?></lastmod>
<?php endif ?>
	</url>
<?php endforeach ?>
</urlset>
