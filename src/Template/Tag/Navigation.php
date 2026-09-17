<?php
namespace Novaris\Template\Tag;

use Novaris\Contracts\{ Displayable, Renderable };
use Symfony\Component\HttpFoundation\Request;

use function Novaris\Theme\Menu\normalize_path;

class Navigation implements Displayable, Renderable
{
    protected array $items;
    protected array $display;
    protected string $currentPath;

    public function __construct( array $items = [], array $options = [] )
    {
        // Use Symfony Request to get the current path.
        $request = Request::createFromGlobals();
        $this->currentPath = normalize_path( $request->getPathInfo() );

        // Initialize items and display settings.
        $this->items = $items;
        $this->display = array_merge( [
            'nav_class'     => 'primary-menu',
            'list_tag'      => 'ul',
            'list_class'    => 'menu-items',
            'item_tag'      => 'li',
            'item_class'    => 'menu-item',
            'anchor_class'  => 'menu-item-anchor',
            'current_class' => 'current-menu-item'
        ], $options );
    }

    public function setItems( array $items ): void
    {
        $this->items = $items;
    }

    public function display(): void
    {
        echo $this->render();
    }

    public function render(): string
    {
        $listItems = array_map(
            [ $this, 'formatItem' ],
            array_keys( $this->items ),
            $this->items
        );

        return sprintf(
            '<nav class="%s"><%s class="%s">%s</%s></nav>',
            e( $this->display['nav_class'] ),
            escape_tag( $this->display['list_tag'] ),
            e( $this->display['list_class'] ),
            implode( '', $listItems ),
            escape_tag( $this->display['list_tag'] )
        );
    }

    private function formatItem( string $name, string $url ): string
    {
        $itemPath  = normalize_path( uri( $url ) );
        $isCurrent = $this->currentPath === $itemPath;

        $itemClass = trim(
            $this->display['item_class']
            . ( $isCurrent ? " {$this->display['current_class']}" : '' )
        );

        $ariaCurrent = $isCurrent ? ' aria-current="page"' : '';

        return sprintf(
            '<%1$s class="%2$s"><a href="%3$s" class="%4$s"%5$s>%6$s</a></%1$s>',
            escape_tag( $this->display['item_tag'] ),
            e( $itemClass ),
            e( $url ),
            e( $this->display['anchor_class'] ),
            $ariaCurrent,
            e( $name )
        );
    }
}