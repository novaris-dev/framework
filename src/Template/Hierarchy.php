<?php
/**
 * Static utility class for building view template hierarchies.
 *
 * Provides template hierarchies used by the view engine to resolve the
 * appropriate views for the current request.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Template;

use Novaris\Contracts\Content\{ContentEntry, ContentType};

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
		$post_type  = $entry->type()->name();

		return array_merge( $entry->viewPaths(), [
			"single.{$entry_name}",
			"single-{$post_type}",
			'single'
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

		return array_merge( $entry->viewPaths(), [
			"page-{$entry_name}",
			'page',
		] );
	}

	/**
	 * Returns the error 404 single template hierarchy.
	 *
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
	public static function taxonomy( ContentEntry $entry ): array
	{
		$entry_name = $entry->name();
		$type_name  = $entry->type()->name();

		return [
			"{$type_name}-{$entry_name}",
			$type_name,
			'archive'
		];
	}

	/**
	 * Returns the author archive template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function author( string $author ): array
	{
		return [
			"author-{$author}",
			'author',
			'archive'
		];
	}

	/**
	 * Returns the date collection template hierarchy.
	 *
	 * @since 1.0.0
	 */
	public static function archive( ContentType $type ): array
	{
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