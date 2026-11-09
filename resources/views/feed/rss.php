<?php
/**
 * Default RSS 2.0 feed.
 *
 * Used when the theme has no feed template. A theme can replace it with
 * `resources/views/feed/rss.php`, or `feed.php` for both feed formats.
 *
 * @var \Novaris\Contracts\Feed\FeedWriter     $feed
 * @var \Novaris\Contracts\Content\ContentEntry $single
 */

$xml      = fn( $value ) => htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
$cdata    = fn( $value ) => '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', (string) $value ) . ']]>';
$taxonomy = $single->type()->feedTaxonomy();
$built    = $feed->updated() ?: $feed->published();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
	<title><?= $xml( $feed->title() ) ?></title>
	<link><?= $xml( $feed->url() ) ?></link>
	<atom:link href="<?= $xml( $single->type()->rssFeedUrl() ) ?>" rel="self" type="application/rss+xml" />
	<description><?= $xml( trim( html_entity_decode( strip_tags( $feed->description() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) ?></description>
	<language><?= $xml( $feed->language() ) ?></language>
<?php if ( $feed->copyright() ) : ?>
	<copyright><?= $xml( $feed->copyright() ) ?></copyright>
<?php endif ?>
<?php if ( $built ) : ?>
	<lastBuildDate><?= $xml( $built ) ?></lastBuildDate>
<?php endif ?>
	<ttl><?= (int) $feed->ttl() ?></ttl>
<?php foreach ( $feed->collection() as $entry ) : ?>
<?php if ( ! $entry->isPublic() || ! $entry->url() ) { continue; } ?>
<?php $author = $entry->author(); ?>
	<item>
		<title><?= $xml( $entry->title() ) ?></title>
		<link><?= $xml( $entry->url() ) ?></link>
		<guid isPermaLink="true"><?= $xml( $entry->url() ) ?></guid>
<?php if ( $entry->published( DATE_RSS ) ) : ?>
		<pubDate><?= $xml( $entry->published( DATE_RSS ) ) ?></pubDate>
<?php endif ?>
<?php if ( $author ) : ?>
		<dc:creator><?= $xml( $author->title() ) ?></dc:creator>
<?php endif ?>
<?php if ( $taxonomy && ( $terms = $entry->terms( $taxonomy->name() ) ) ) : ?>
<?php foreach ( $terms as $term ) : ?>
		<category><?= $xml( $term->title() ) ?></category>
<?php endforeach ?>
<?php endif ?>
		<description><?= $xml( trim( html_entity_decode( strip_tags( $entry->excerpt() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) ?></description>
		<content:encoded><?= $cdata( $entry->content() ) ?></content:encoded>
	</item>
<?php endforeach ?>
</channel>
</rss>
