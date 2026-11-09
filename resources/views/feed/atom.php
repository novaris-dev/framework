<?php
/**
 * Default Atom feed.
 *
 * Used when the theme has no feed template. A theme can replace it with
 * `resources/views/feed/atom.php`, or `feed.php` for both feed formats.
 *
 * @var \Novaris\Contracts\Feed\FeedWriter     $feed
 * @var \Novaris\Contracts\Content\ContentEntry $single
 */

$xml      = fn( $value ) => htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
$taxonomy = $single->type()->feedTaxonomy();
$updated  = $feed->updated() ?: $feed->published() ?: date( DATE_ATOM );

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="<?= $xml( $feed->language() ) ?>">
	<title><?= $xml( $feed->title() ) ?></title>
<?php if ( $subtitle = trim( html_entity_decode( strip_tags( $feed->description() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) : ?>
	<subtitle><?= $xml( $subtitle ) ?></subtitle>
<?php endif ?>
	<link rel="alternate" type="text/html" href="<?= $xml( $feed->url() ) ?>" />
	<link rel="self" type="application/atom+xml" href="<?= $xml( $single->type()->atomFeedUrl() ) ?>" />
	<id><?= $xml( $single->type()->atomFeedUrl() ) ?></id>
	<updated><?= $xml( $updated ) ?></updated>
<?php if ( $feed->copyright() ) : ?>
	<rights><?= $xml( $feed->copyright() ) ?></rights>
<?php endif ?>
<?php foreach ( $feed->collection() as $entry ) : ?>
<?php if ( ! $entry->isPublic() || ! $entry->url() ) { continue; } ?>
<?php $author = $entry->author(); ?>
	<entry>
		<title><?= $xml( $entry->title() ) ?></title>
		<link rel="alternate" type="text/html" href="<?= $xml( $entry->url() ) ?>" />
		<id><?= $xml( $entry->url() ) ?></id>
		<updated><?= $xml( $entry->updated( DATE_ATOM ) ?: $entry->published( DATE_ATOM ) ?: $updated ) ?></updated>
<?php if ( $entry->published( DATE_ATOM ) ) : ?>
		<published><?= $xml( $entry->published( DATE_ATOM ) ) ?></published>
<?php endif ?>
<?php if ( $author ) : ?>
		<author><name><?= $xml( $author->title() ) ?></name></author>
<?php endif ?>
<?php if ( $taxonomy && ( $terms = $entry->terms( $taxonomy->name() ) ) ) : ?>
<?php foreach ( $terms as $term ) : ?>
		<category term="<?= $xml( $term->name() ) ?>" label="<?= $xml( $term->title() ) ?>" />
<?php endforeach ?>
<?php endif ?>
		<summary><?= $xml( trim( html_entity_decode( strip_tags( $entry->excerpt() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) ?></summary>
		<content type="html"><?= $xml( $entry->content() ) ?></content>
	</entry>
<?php endforeach ?>
</feed>
