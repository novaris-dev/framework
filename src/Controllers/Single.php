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
            // ClassicPress API call
            if (method_exists($type, 'isDirectory') && $type->isDirectory()) {
                try {
                    $client = new Client([
                        'base_uri' => 'https://directory.classicpress.net', // ClassicPress API endpoint
                        'timeout'  => 5.0,
                    ]);

                    $title = $single->title();
                    $slug = $this->slugify($title);

                    $response = $client->request('GET', "/wp-json/wp/v2/themes?byslug={$slug}");
                    $apiData = json_decode($response->getBody()->getContents(), true);

                    $single->api = $apiData;
                } catch (\Exception $e) {
                    $single->api = ['error' => 'Failed to fetch API data from ClassicPress'];
                }
            }

            // WordPress API call
            try {
                $wp_client = new Client([
                    'base_uri' => 'https://api.wordpress.org', // WordPress API base URL
                    'timeout'  => 5.0,
                ]);

                $slug = esc_attr($this->slugify($single->title())); // Sanitize and slugify the title
                $wp_response = $wp_client->request('GET', "/themes/info/1.1/?action=theme_information&request[slug]={$slug}");

                $wp_apiData = json_decode($wp_response->getBody()->getContents(), true);

                // Embed WordPress API data into the $single object
                $single->wp_api = $wp_apiData;

            } catch (\Exception $e) {
                // Handle the exception and log error
                $single->wp_api = ['error' => 'Failed to fetch API data from WordPress'];
            }

            $type_name = sanitize_slug($type->type());
            $collection = false;

            if ($args = $single->collectionArgs()) {
                $collection = Query::make($args);
            }

            $doctitle = new DocumentTitle($single->title());

            // Pass the single content (with both API data) to the view
            return $this->response($this->view(
                Hierarchy::single($single),
                [
                    'doctitle'   => $doctitle,
                    'pagination' => false,
                    'entry'      => $single,   // Single content with embedded API data
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
