<?php
namespace Novaris\Template\Tag;

use Novaris\Contracts\{ Displayable, Renderable };
use Novaris\Tools\Str;
use Symfony\Component\HttpFoundation\Request;

class Navigation implements Displayable, Renderable
{
    protected array $items;
    protected array $display;
    protected string $currentPath;

    public function __construct( array $items = [], array $options = [] )
    {
        // Use Symfony Request to get the current path
        $request = Request::createFromGlobals();
        $this->currentPath = $request->getPathInfo();

        // Initialize items and display settings
        $this->items = $items;
        $this->display = array_merge( [
            'nav_class'      => 'primary-menu',
            'list_tag'       => 'ul',
            'list_class'     => 'menu-items',
            'item_tag'       => 'li',
            'item_class'     => 'menu-item',
            'anchor_class'   => 'menu-item-anchor',
            'current_class'  => 'current-menu-item-%s'
        ], $options );
    }

    public function setItems( array $items ): void
    {
        $this->items = $items;
    }

    public function display(): void
    {
        // Only echo the output of render()
        echo $this->render();
    }

    public function render(): string
    {
        $listItems = array_map( [ $this, 'formatItem' ], array_keys( $this->items ), $this->items );

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
        $isCurrent = $this->currentPath === $url;
        $currentClass = $isCurrent ? strtolower( sprintf( $this->display['current_class'], $name ) ) : '';

        $itemClass = trim( $this->display['item_class'] . ( $currentClass ? " $currentClass" : '' ) );
        $anchorClass = $this->display['anchor_class'];

        return sprintf(
            '<%1$s class="%2$s"><a href="%3$s" class="%4$s">%5$s</a></%1$s>',
            $this->display['item_tag'],
            e( $itemClass ),
            e( $url ),
            e( $anchorClass ),
            e( $name )
        );
    }
}
