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
    public function __invoke( array $params, Request $request ): Response
    {
        $types = App::resolve( 'content.types' );

        // Check if another content type is part of the request. In
        // particular, this is mostly used for taxonomies used in the URL.
        foreach ( $types as $type ) {

            if ( isset( $params[ $type->name() ] ) ) {
                $meta_key   = $type->name();
                $meta_value = $params[ $type->name() ];
            }
        }

        // Get the post name and path.
        $name = $params['name'];
        $path = $params['path'] ?? '';
        $parts = explode( '/', $path );

        // Explodes the path into parts and loops through each. Strips
        // the last part off the original path with each iteration and
        // checks if it's the path or URI for the content type. If a
        // match is found, break out of the loop.
        foreach ( array_reverse( $parts ) as $part ) {
            $path = Str::beforeLast( $path, "/{$part}" );

            // Check type by path and URI.
            if ( $type = $types->getTypeFromPath( $path ) ) {
                break;
            } elseif ( $type = $types->getTypeFromUri( $path ) ) {
                break;
            }
        }

        // Make a query to get the single entry
        $single = Query::make( [
            'path'       => $type ? $type->path() : $path,
            'slug'       => $name,
            'year'       => $params['year']   ?? null,
            'month'      => $params['month']  ?? null,
            'day'        => $params['day']    ?? null,
            'author'     => $params['author'] ?? null,
            'meta_key'   => $meta_key         ?? null,
            'meta_value' => $meta_value       ?? null
        ] )->single();

        if ( $single && $single->isPublic() ) {
            // If the single entry is public and is part of a directory, proceed with API call
            if (method_exists($type, 'isDirectory') && $type->isDirectory()) {
                try {
                    // Instantiate Guzzle client
                    $client = new Client([
                        'base_uri' => 'https://directory.classicpress.net', // ClassicPress API endpoint
                        'timeout'  => 5.0,
                    ]);

                    // Use the $single->title() to create a slug
                    $title = $single->title(); // Get the title from the single object
                    $slug = $this->slugify($title); // Call the custom slugify method

                    // Fetch data from the ClassicPress API using the slug
                    $response = $client->request('GET', "/wp-json/wp/v2/themes?byslug={$slug}");
                    $apiData = json_decode($response->getBody()->getContents(), true);

                    // Embed API data into the $single object
                    $single->api = $apiData;

                } catch (\Exception $e) {
                    // Handle the exception and log error
                    $single->api = ['error' => 'Failed to fetch API data from ClassicPress'];
                }
            } else {
				$single->api = null;
			}

            $type_name  = sanitize_slug( $type->type() );
            $collection = false;

            if ( $args = $single->collectionArgs() ) {
                $collection = Query::make( $args );
            }

            $doctitle = new DocumentTitle( $single->title() );

            // Pass the single content (with API data) to the view
            return $this->response( $this->view(
                Hierarchy::single( $single ),
                [
                    'doctitle'   => $doctitle,
                    'pagination' => false,
                    'entry'      => $single,   // Single content with embedded API data
                    'collection' => $collection
                ]
            ) );
        }

        // If all else fails, return a 404.
        return $this->forward404( $params, $request );
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
