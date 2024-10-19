<?php
/**
 * Single controller.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Controllers;

use GuzzleHttp\Client; // Import Guzzle Client
use Novaris\Core\Proxies\{App, Query};
use Novaris\Template\Hierarchy;
use Novaris\Template\Tag\DocumentTitle;
use Novaris\Tools\Str;
use Symfony\Component\HttpFoundation\{Request, Response};

class Single extends Controller
{
    /**
     * Callback method when route matches request.
     *
     * @since 1.0.0
     */
    public function __invoke(array $params, Request $request): Response
    {
        $types = App::resolve('content.types');

        foreach ($types as $type) {
            if (isset($params[$type->name()])) {
                $meta_key = $type->name();
                $meta_value = $params[$type->name()];
            }
        }

        $name = $params['name'];
        $path = $params['path'] ?? '';
        $parts = explode('/', $path);

        foreach (array_reverse($parts) as $part) {
            $path = Str::beforeLast($path, "/{$part}");

            if ($type = $types->getTypeFromPath($path)) {
                break;
            } elseif ($type = $types->getTypeFromUri($path)) {
                break;
            }
        }

        $single = Query::make([
            'path'       => $type ? $type->path() : $path,
            'slug'       => $name,
            'year'       => $params['year'] ?? null,
            'month'      => $params['month'] ?? null,
            'day'        => $params['day'] ?? null,
            'author'     => $params['author'] ?? null,
            'meta_key'   => $meta_key ?? null,
            'meta_value' => $meta_value ?? null
        ])->single();

        if ($single && $single->isPublic()) {
            $slug = $this->slugify($single->title()); // Generate slug

            // ClassicPress APIs (Themes and Plugins)
            if (method_exists($type, 'isDirectory') && $type->isDirectory()) {
                try {
                    $client = new Client([
                        'base_uri' => 'https://directory.classicpress.net',
                        'timeout'  => 5.0,
                    ]);

                    // Fetch ClassicPress Themes API
                    $cp_themes_response = $client->request('GET', "/wp-json/wp/v2/themes?byslug={$slug}");
                    $cp_themes_apiData = json_decode($cp_themes_response->getBody()->getContents(), true);
                    $single->cp_themes_api = $cp_themes_apiData;

                    // Fetch ClassicPress Plugins API
                    $cp_plugins_response = $client->request('GET', "/wp-json/wp/v2/plugins?byslug={$slug}");
                    $cp_plugins_apiData = json_decode($cp_plugins_response->getBody()->getContents(), true);
                    $single->cp_plugins_api = $cp_plugins_apiData;

                } catch (\Exception $e) {
                    $single->cp_themes_api = ['error' => 'Failed to fetch data from ClassicPress Themes API'];
                    $single->cp_plugins_api = ['error' => 'Failed to fetch data from ClassicPress Plugins API'];
                }
            }

            // WordPress APIs (Themes and Plugins)
            try {
                $wp_client = new Client([
                    'base_uri' => 'https://api.wordpress.org',
                    'timeout'  => 5.0,
                ]);

                // Fetch WordPress Themes API
                $wp_themes_response = $wp_client->request('GET', "/themes/info/1.1/?action=theme_information&request[slug]={$slug}");
                $wp_themes_apiData = json_decode($wp_themes_response->getBody()->getContents(), true);
                $single->wp_themes_api = $wp_themes_apiData;

                // Fetch WordPress Plugins API
                $wp_plugins_response = $wp_client->request('GET', "/plugins/info/1.1/?action=plugin_information&request[slug]={$slug}");
                $wp_plugins_apiData = json_decode($wp_plugins_response->getBody()->getContents(), true);
                $single->wp_plugins_api = $wp_plugins_apiData;

            } catch (\Exception $e) {
                $single->wp_themes_api = ['error' => 'Failed to fetch data from WordPress Themes API'];
                $single->wp_plugins_api = ['error' => 'Failed to fetch data from WordPress Plugins API'];
            }

            $type_name = sanitize_slug($type->type());
            $collection = false;

            if ($args = $single->collectionArgs()) {
                $collection = Query::make($args);
            }

            $doctitle = new DocumentTitle($single->title());

            // Pass the single content (with all API data) to the view
            return $this->response($this->view(
                Hierarchy::single($single),
                [
                    'doctitle'   => $doctitle,
                    'pagination' => false,
                    'entry'      => $single,
                    'collection' => $collection
                ]
            ));
        }

        // If all else fails, return a 404.
        return $this->forward404($params, $request);
    }

    /**
     * Helper method to convert a title into a slug.
     *
     * @param string $title The title to convert.
     * @return string The slugified version of the title.
     */
    protected function slugify($title)
    {
        // Convert to lowercase
        $slug = strtolower($title);

        // Remove any characters that are not alphanumeric, spaces, or hyphens
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);

        // Replace spaces and multiple hyphens with a single hyphen
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        // Trim hyphens from both ends of the slug
        return trim($slug, '-');
    }
}
