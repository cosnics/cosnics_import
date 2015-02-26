<?php
namespace Chamilo\Libraries\Format\Tabs;


use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

class DynamicVisualTab extends DynamicTab
{
    // Position constants
    const POSITION_LEFT = 'left';
    const POSITION_RIGHT = 'right';

    // Display constants
    const DISPLAY_ICON = 1;
    const DISPLAY_TEXT = 2;
    const DISPLAY_BOTH = 3;
    const DISPLAY_BOTH_SELECTED = 4;

    // Target constants
    const TARGET_WINDOW = 1;
    const TARGET_POPUP = 2;

    /**
     *
     * @var string
     */
    private $content;

    /**
     *
     * @var boolean
     */
    private $selected;

    /**
     *
     * @var boolean
     */
    private $confirmation;

    /**
     *
     * @var string
     */
    private $position;

    /**
     *
     * @var int
     */
    private $display;

    /**
     *
     * @var int
     */
    private $target;

    /**
     *
     * @param integer $id
     * @param string $name
     * @param string $image
     * @param string $link
     * @param boolean $selected
     * @param boolean,string $confirmation
     */
    public function __construct($id, $name, $image, $link, $selected = false, $confirmation = false,
        $position = self :: POSITION_LEFT, $display = self :: DISPLAY_BOTH, $target = self :: TARGET_WINDOW)
    {
        parent :: __construct($id, $name, $image);
        $this->link = $link;
        $this->selected = $selected;
        $this->confirmation = $confirmation;
        $this->position = $position;
        $this->display = $display;
        $this->target = $target;
    }

    /**
     *
     * @return the $link
     */
    public function get_link()
    {
        return $this->link;
    }

    /**
     *
     * @param $link the link to set
     */
    public function set_link($link)
    {
        $this->link = $link;
    }

    /**
     *
     * @return the $selected
     */
    public function get_selected()
    {
        return $this->selected;
    }

    /**
     *
     * @param $selected the selected to set
     */
    public function set_selected($selected)
    {
        $this->selected = $selected;
    }

    /**
     *
     * @return string
     */
    public function get_position()
    {
        return $this->position;
    }

    /**
     *
     * @param string $position
     */
    public function set_position($position)
    {
        $this->position = $position;
    }

    /**
     *
     * @return boolean,string
     */
    public function get_confirmation()
    {
        return $this->confirmation;
    }

    /**
     *
     * @param boolean,string $confirmation
     */
    public function set_confirmation($confirmation)
    {
        $this->confirmation = $confirmation;
    }

    /**
     *
     * @return boolean
     */
    public function needs_confirmation()
    {
        if ($this->get_confirmation() === false)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     *
     * @return int
     */
    public function get_display()
    {
        return $this->display;
    }

    /**
     *
     * @param string $display
     */
    public function set_display($display)
    {
        $this->display = $display;
    }

    /**
     *
     * @return int
     */
    public function get_target()
    {
        return $this->target;
    }

    /**
     *
     * @param int $target
     */
    public function set_target($target)
    {
        $this->target = $target;
    }

    /**
     *
     * @param string $tab_name
     * @return string
     */
    public function header()
    {
        if ($this->get_selected() == true)
        {
            $classes = 'ui-state-default ui-corner-top ui-state-active ui-tabs-selected dynamic_visual_tab';
        }
        else
        {
            $classes = 'ui-state-default ui-corner-top';
        }

        $html = array();
        $html[] = '<li class="' . $classes . '" style="float: ' . $this->get_position() . ';">';

        $link = array();
        $link[] = '<a';

        if ($this->get_link() && $this->get_target() == self :: TARGET_WINDOW)
        {
            $link[] = 'href="' . $this->get_link() . '"';

            if ($this->needs_confirmation())
            {
                $link[] = 'onclick="return confirm(\'' . addslashes(
                    htmlentities(
                        $this->get_confirmation() === true ? Translation :: get(
                            'Confirm',
                            null,
                            Utilities :: COMMON_LIBRARIES) : $this->get_confirmation())) . '\');"';
            }
        }
        elseif ($this->get_link() && $this->get_target() == self :: TARGET_POPUP)
        {
            $link[] = 'onclick="javascript:openPopup(\'' . $this->get_link() . '\'); return false"';
        }
        else
        {
            $link[] = 'style="cursor: default;"';
        }

        $link[] = '>';

        $html[] = implode(' ', $link);
        $html[] = '<span class="category">';

        if ($this->get_image() && $this->get_display() != self :: DISPLAY_TEXT)
        {
            $html[] = '<img src="' . $this->get_image() . '" border="0" style="vertical-align: middle;" alt="' .
                 $this->get_name() . '" title="' . $this->get_name() . '"/>';
        }

        if ($this->get_name() && (($this->get_display() == self :: DISPLAY_BOTH_SELECTED && $this->get_selected() == true) ||
             $this->get_display() == self :: DISPLAY_ICON || $this->get_display() == self :: DISPLAY_BOTH))
        {
            $html[] = '<span class="title">' . $this->get_name() . '</span>';
        }

        $html[] = '</span>';
        $html[] = '</a>';
        $html[] = '</li>';
        return implode("\n", $html);
    }

    /**
     *
     * @param string $tab_name
     * @return string
     */
    public function body()
    {
        return null;
    }
}
