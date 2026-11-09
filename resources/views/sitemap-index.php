<?php
/**
 * Default sitemap index, listing each content type's sitemap.
 *
 * Used when the theme has no `resources/views/sitemap-index.php`.
 *
 * @var iterable<\Novaris\Contracts\Content\ContentType> $sitemaps
 */

$xml = fn( $value ) => htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ( $sitemaps as $type ) : ?>
	<sitemap>
		<loc><?= $xml( $type->sitemapUrl() ) ?></loc>
	</sitemap>
<?php endforeach ?>
</sitemapindex>
