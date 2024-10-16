<?php
/**
 * Static utility class for building a top-level template hierarchy.
 *
 * IMPORTANT! This class and its method should not be considered finalized. This
 * is an experimental method for cleaning up some of the code in our controllers.
 * I'm not 100% happy with the code and want to explore various methods for
 * creating as small and consistent of a footprint as possible.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template;

use Novaris\Contracts\Content\{ContentEntry, ContentType};
use Novaris\Template\Feed\Feed;

class Hierarchy
{
	/**
	 * Returns the default single template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function single( ContentEntry $entry ): array
	{
		$entry_name = $entry->name();
		$type_name  = $entry->type()->name();
		$model_name = static::modelName( $entry->type() );

		return array_merge( $entry->viewPaths(), [
			"single-{$entry_name}",
			'single',
		] );
	}

	/**
	 * Returns the default page template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function page( ContentEntry $entry ): array
	{
		$entry_name = $entry->name();
		$type_name  = $entry->type()->name();
		$model_name = static::modelName( $entry->type() );

		return array_merge( $entry->viewPaths(), [
			"page-{$entry_name}",
			'page',
		] );
	}

	/**
	 * Returns the error 404 single template hierarchy.
	 *
	 * @todo  Create an `error` content type.
	 * @since 1.0.0
	 */
	public static function error404(): array
	{
		return [
			'404',
		];
	}

	/**
	 * Returns the homepage single template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function singleHome( ContentEntry $entry ): array
	{
		$entry_name = $entry->name();
		$type_name  = $entry->type()->name();
		$model_name = static::modelName( $entry->type() );

		return array_merge( $entry->viewPaths(), [
			'index'
		] );
	}

	/**
	 * Returns the default collection template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function collection( ContentEntry $entry ): array
	{
		$type_name  = $entry->type()->name();
		$model_name = static::modelName( $entry->type() );

		return [
			"collection-{$type_name}",
			"collection-{$model_name}",
			'collection',
		];
	}

	/**
	 * Returns the homepage collection template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function collectionHome( ContentEntry $entry ): array
	{
		return array_merge( [
			'collection-home'
		], static::collection( $entry ) );
	}

	/**
	 * Returns the term collection template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function collectionTerm( ContentEntry $entry ): array
	{
		$entry_name = $entry->name();
		$type_name  = $entry->type()->name();

		return [
			"collection-{$type_name}-{$entry_name}",
			"collection-{$type_name}",
			'collection-term',
			'collection',
			'index'
		];
	}

	/**
	 * Returns the date collection template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function archive( ContentType $type ): array {

		return [
			'archive'
		];
	}

	/**
	 * Helper method for getting a content type's model name. This is a
	 * precursor to a larger content-type blueprint object planned for the
	 * future.
	 *
	 * @since 1.0.0
	 */
	protected static function modelName( ContentType $type ): string
	{
		return $type->isTaxonomy() ? 'taxonomy' : 'content';
	}
}
