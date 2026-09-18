<?php
/**
 * Directory repository.
 *
 * Handles external directory requests for ClassicPress and WordPress
 * themes and plugins.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Directory;

use GuzzleHttp\Client;

class Repository
{
	/**
	 * ClassicPress API client.
	 *
	 * @since 1.0.0
	 */
	protected Client $classicpress;

	/**
	 * WordPress API client.
	 *
	 * @since 1.0.0
	 */
	protected Client $wordpress;

	/**
	 * Create a new directory repository.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->classicpress = new Client( [
			'base_uri' => 'https://directory.classicpress.net',
			'timeout'  => 5.0,
		] );

		$this->wordpress = new Client( [
			'base_uri' => 'https://api.wordpress.org',
			'timeout'  => 5.0,
		] );
	}

	/**
	 * Gets directory information for the given slug.
	 *
	 * @since 1.0.0
	 */
    public function get( string $slug ): array
    {
        $cp_theme = $this->classicPressTheme( $slug );

        $data = [
            'cp_themes_api'  => $cp_theme,
            'cp_plugins_api' => $this->classicPressPlugin( $slug ),
            'wp_plugins_api' => $this->wordPressPlugin( $slug ),
        ];

        if ( isset( $cp_theme['error'] ) ) {
            $data['wp_themes_api'] = $this->wordPressTheme( $slug );
        }

        return $data;
    }

	/**
	 * Gets ClassicPress theme information.
	 *
	 * @since 1.0.0
	 */
	protected function classicPressTheme( string $slug ): array
	{
		try {
			$response = $this->classicpress->request(
				'GET',
				"/wp-json/wp/v2/themes?byslug={$slug}"
			);

			$data = json_decode(
				$response->getBody()->getContents(),
				true
			);

			if ( ! empty( $data ) ) {
				return $data;
			}
		} catch ( \Exception $e ) {
			//
		}

		return [
			'error' => 'Failed to fetch data from ClassicPress Themes API. Trying WordPress...'
		];
	}

	/**
	 * Gets WordPress theme information.
	 *
	 * This is intended as the fallback when the ClassicPress theme
	 * directory does not contain the requested theme.
	 *
	 * @since 1.0.0
	 */
	protected function wordPressTheme( string $slug ): array
	{
		try {
			$response = $this->wordpress->request(
				'GET',
				"/themes/info/1.2/?action=theme_information&request[slug]={$slug}"
			);

			$data = json_decode(
				$response->getBody()->getContents(),
				true
			);

			if ( ! empty( $data ) ) {
				return $data;
			}

		} catch ( \Exception $e ) {
			return [
				'error' => 'Failed to fetch data from WordPress Themes API: ' . $e->getMessage()
			];
		}

		return [
			'error' => 'Failed to fetch data from WordPress Themes API.'
		];
	}

	/**
	 * Gets ClassicPress plugin information.
	 *
	 * @since 1.0.0
	 */
	protected function classicPressPlugin( string $slug ): array
	{
		try {
			$response = $this->classicpress->request(
				'GET',
				"/wp-json/wp/v2/plugins?byslug={$slug}"
			);

			return json_decode(
				$response->getBody()->getContents(),
				true
			) ?: [];

		} catch ( \Exception $e ) {
			return [
				'error' => 'Failed to fetch data from ClassicPress Plugins API'
			];
		}
	}

	/**
	 * Gets WordPress plugin information.
	 *
	 * @since 1.0.0
	 */
	protected function wordPressPlugin( string $slug ): array
	{
		try {
			$response = $this->wordpress->request(
				'GET',
				"/plugins/info/1.1/?action=plugin_information&request[slug]={$slug}"
			);

			return json_decode(
				$response->getBody()->getContents(),
				true
			) ?: [];

		} catch ( \Exception $e ) {
			return [
				'error' => 'Failed to fetch data from WordPress Plugins API'
			];
		}
	}
}