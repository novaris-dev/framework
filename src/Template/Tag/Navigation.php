<?php
namespace Novaris\Template\Tag;

use Novaris\Contracts\{Displayable, Renderable};
use Novaris\Tools\Str;

class Navigation implements Displayable, Renderable
{
    protected array $items = [];
    protected array $display = [];
    protected string $currentPath;

    public function __construct(array $items = [], array $options = [])
    {
        $this->items = $items;
        $this->currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $this->display = array_merge([
            'nav_class'      => 'primary-menu',
            'list_tag'       => 'ul',
            'list_class'     => 'menu-items',
            'item_tag'       => 'li',
            'item_class'     => 'menu-item',
            'anchor_class'   => 'menu-item-anchor',
            'current_class'  => 'current-menu-item-%s' // Dynamic class for active item
        ], $options);
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function display(array $options = []): void
    {
        $this->display = array_merge($this->display, $options);
        echo $this->render();
    }

    public function render(): string
    {
        $list = '';
        foreach ($this->items as $name => $url) {
            $list .= $this->formatItem($name, $url);
        }

        return sprintf(
            '<nav class="%1$s"><%2$s class="%3$s">%4$s</%2$s></nav>',
            e($this->display['nav_class']),
            escape_tag($this->display['list_tag']),
            e($this->display['list_class']),
            $list
        );
    }

    private function formatItem(string $name, string $url): string
    {
        $isCurrent = $this->currentPath === $url;
        $currentClass = $isCurrent ? strtolower(sprintf($this->display['current_class'], $name)) : '';

        $itemClass = $this->display['item_class'] . ($currentClass ? " $currentClass" : '');
        $anchorClass = $this->display['anchor_class'];

        return sprintf(
            '<%1$s class="%2$s"><a href="%3$s" class="%4$s">%5$s</a></%1$s>',
            $this->display['item_tag'],
            e($itemClass),
            e($url),
            e($anchorClass),
            e($name)
        );
    }
}
