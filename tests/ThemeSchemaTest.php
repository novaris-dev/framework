<?php
/**
 * theme.json schema tests.
 *
 * Keeps `schemas/theme.schema.json` in step with the rules the framework
 * applies in PHP, so editors warn about exactly what Novaris rejects.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Tests;

use Novaris\Theme\Name;

class ThemeSchemaTest extends TestCase
{
	/**
	 * Theme names to compare, valid and invalid.
	 */
	private const NAMES = [
		'amicable', 'theme_2.0', 'my.theme', 'A1', 'Org-Name/my.theme',
		'user123/x', 'novaris-dev/amicable', 'someone/their-theme',
		'', '..', '../x', 'x/..', 'a/../b', 'a..b', 'a/b/c', '/x', 'x/',
		'.hidden', '-owner/x', 'owner-/x', 'own--er/x', 'a b', 'x\\y',
		'-x', '_x', 'owner/.x', 'owner/-x', 'ow_ner/x', 'ow.ner/x',
	];

	/**
	 * Returns the decoded schema.
	 */
	private function schema(): array
	{
		$file = dirname( __DIR__ ) . '/schemas/theme.schema.json';

		$this->assertFileExists( $file );

		$schema = json_decode( file_get_contents( $file ), true );

		$this->assertIsArray( $schema, 'theme.schema.json is valid JSON' );

		return $schema;
	}

	/**
	 * Whether a value matches one of the schema's `pattern` properties.
	 */
	private function schemaAccepts( string $property, string $value ): bool
	{
		$pattern = $this->schema()['properties'][ $property ]['pattern'];

		return 1 === preg_match( '#' . $pattern . '#', $value );
	}

	public function testSchemaRequiresTheFieldsTheFrameworkNeeds(): void
	{
		$schema = $this->schema();

		$this->assertSame( 'http://json-schema.org/draft-07/schema#', $schema['$schema'] );
		$this->assertSame( [ 'name', 'slug', 'version' ], $schema['required'] );
		$this->assertFalse( $schema['additionalProperties'], 'unknown fields are flagged' );
	}

	public function testThemesWithASchemaLineStillLoad(): void
	{
		$site = $this->makeSite( [
			'themes/solo/theme.json' => json_encode( [
				'$schema' => 'https://raw.githubusercontent.com/novaris-dev/framework/develop/schemas/theme.schema.json',
				'name'    => 'Solo',
				'slug'    => 'solo',
				'version' => '1.0.0',
			] ),
		], [
			'private' => false,
			'theme'   => 'solo',
		] );

		$this->assertSame( "{$site}/themes/solo", $this->boot( $site )->themePath() );
	}

	public function testParentPatternMatchesThemeNameRules(): void
	{
		foreach ( self::NAMES as $name ) {
			$this->assertSame( Name::isValid( $name ), $this->schemaAccepts( 'parent', $name ), var_export( $name, true ) );
		}
	}

	public function testSlugPatternMatchesThemeNamesWithoutAnOwner(): void
	{
		foreach ( self::NAMES as $name ) {
			$expected = Name::isValid( $name ) && ! str_contains( $name, '/' );

			$this->assertSame( $expected, $this->schemaAccepts( 'slug', $name ), var_export( $name, true ) );
		}
	}

	public function testRepositoryPatternMatchesThemeNamesWithAnOwner(): void
	{
		foreach ( self::NAMES as $name ) {
			$expected = Name::isValid( $name ) && str_contains( $name, '/' );

			$this->assertSame( $expected, $this->schemaAccepts( 'repository', $name ), var_export( $name, true ) );
		}
	}

	public function testVersionPatternAcceptsReleaseVersions(): void
	{
		foreach ( [ '1.0.0', '0.2.0', '10.20.30', '1.2.0-beta.1', '1.0.0+build.5' ] as $version ) {
			$this->assertTrue( $this->schemaAccepts( 'version', $version ), $version );
		}

		foreach ( [ 'v1.0.0', '1.0', '1', '1.0.0.0', 'latest', '' ] as $version ) {
			$this->assertFalse( $this->schemaAccepts( 'version', $version ), $version );
		}
	}
}
