<?php
namespace Chamilo\Libraries\Format\Structure;

/**
 * $Id: toolbar.class.php 128 2009-11-09 13:13:20Z vanpouckesven $
 * 
 * @package common.html.toolbar
 */
class Toolbar
{
    const TYPE_HORIZONTAL = 'horizontal';
    const TYPE_VERTICAL = 'vertical';

    /**
     *
     * @var array
     */
    private $items = array();

    /**
     *
     * @var array
     */
    private $class_names = array();

    /**
     *
     * @var string
     */
    private $css = null;

    /**
     *
     * @var string
     */
    private $type;

    /**
     *
     * @param string $type
     * @param array $class_names
     * @param string $css
     */
    public function __construct($type = self :: TYPE_HORIZONTAL, $class_names = array(), $css = null)
    {
        $this->type = $type;
        $this->class_names = $class_names;
        $this->css = $css;
    }

    /**
     *
     * @param array $items
     */
    public function set_items(array $items)
    {
        $this->items = $items;
    }

    /**
     *
     * @return array:
     */
    public function get_items()
    {
        return $this->items;
    }

    /**
     * Returns the toolbaritem from the given position
     * 
     * @param int $index
     * @return ToolbarItem
     */
    public function get_item($index)
    {
        return $this->items[$index];
    }

    /**
     * Inserts an item in the toolbar
     * 
     * @param ToolbarItem $item
     * @param int $index
     */
    public function insert_item(ToolbarItem $item, $index)
    {
        $items = $this->items;
        array_splice($items, $index, 0, array($item));
        $this->items = $items;
    }

    /**
     * Replaces an item in the toolbar
     * 
     * @param ToolbarItem $item
     * @param int $index
     */
    public function replace_item(ToolbarItem $item, $index)
    {
        $items = $this->items;
        array_splice($items, $index, 1, array($item));
        $this->items = $items;
    }

    /**
     *
     * @param ToolbarItem $item
     */
    public function add_item(ToolbarItem $item)
    {
        $this->items[] = $item;
    }

    /**
     *
     * @param ToolbarItem $item
     */
    public function prepend_item(ToolbarItem $item)
    {
        array_unshift($this->items, $item);
    }

    /**
     *
     * @param array $items
     */
    public function add_items($items)
    {
        foreach ($items as $item)
        {
            $this->items[] = $item;
        }
    }

    /**
     *
     * @param string $type
     */
    public function set_type($type)
    {
        $this->type = $type;
    }

    /**
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     *
     * @return string
     */
    public function as_html()
    {
        $toolbar_data = $this->items;
        $type = $this->get_type();
        $class_names = $this->class_names;
        $css = $this->css;
        
        if (! is_array($class_names))
        {
            $class_names = array($class_names);
        }
        $class_names[] = 'toolbar_' . $type;
        
        $html = array();
        $html[] = '<div class="toolbar">';
        $html[] = '<ul class="' . implode(' ', $class_names) . '"' . (isset($css) ? ' style="' . $css . '"' : '') . '>';
        
        foreach ($toolbar_data as $index => $toolbar_item)
        {
            $classes = array();
            
            if ($index == 0)
            {
                $classes[] = 'first';
            }
            
            if ($index == count($toolbar_data) - 1)
            {
                $classes[] = 'last';
            }
            
            $html[] = '<li' . (count($classes) ? ' class="' . implode(' ', $classes) . '"' : '') . '>' .
                 $toolbar_item->as_html() . '</li>';
        }
        
        $html[] = '</ul>';
        $html[] = '<div class="clear">&nbsp;</div>';
        $html[] = '</div>';
        
        return implode($html);
    }

    public function has_items()
    {
        return count($this->items) > 0;
    }
}
