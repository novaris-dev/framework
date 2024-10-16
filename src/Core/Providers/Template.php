<?php
/**
 * Template service provider.
 *
 * @package   Novaris
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024. Benjamin Lu
 * @link      https://github.com/novaris-dev/framework
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Novaris\Core\Providers;

use Novaris\Contracts\Template\{
	TemplateEngine,
	TemplateTag,
	TemplateTags,
	TemplateView
};

use Novaris\Core\ServiceProvider;
use Novaris\Template\{Component, Engine, View};
use Novaris\Template\Tag\{PoweredBy, Tag, Tags};
use Novaris\Tools\Collection;

class Template extends ServiceProvider
{
	/**
	 * Register bindings.
	 *
	 * @since 1.0.0
	 */
        public function register(): void
	{
		// Add template engine.
		$this->app->singleton( TemplateEngine::class, Engine::class );
                $this->app->bind(      TemplateView::class,   View::class   );

		// Bind template tag.
		$this->app->bind( TemplateTag::class, Tag::class );

		// Bind template tags.
		$this->app->singleton( TemplateTags::class, Tags::class );

		// Binds the template component.
		$this->app->singleton( Component::class, function( $app ) {
			return new Component(
				$app->make( TemplateTags::class ),
				$app->make( 'config' )->get( 'template.tags' )
			);
		} );

		// Add powered-by singleton.
		$this->app->singleton( PoweredBy::class );

		// Add aliases.
		$this->app->alias( TemplateTag::class,    'template.tag'    );
		$this->app->alias( TemplateTags::class,   'template.tags'   );
		$this->app->alias( TemplateView::class,   'template.view'   );
		$this->app->alias( TemplateEngine::class, 'template.engine' );
		$this->app->alias( PoweredBy::class,      'poweredby'       );
	}

	/**
	 * Bootstrap bindings.
	 *
	 * @since 1.0.0
	 */
        public function boot(): void
	{
                $this->app->make( Component::class )->boot();
        }
}
