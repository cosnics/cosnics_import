<?php
namespace Chamilo\Core\Repository\Integration\Chamilo\Core\Menu\Storage\DataClass;

use Chamilo\Core\Menu\Storage\DataClass\Item;
use Chamilo\Libraries\Format\Structure\Page;

/**
 *
 * @package Chamilo\Core\User\Integration\Chamilo\Core\Menu\Storage\DataClass
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class WorkspaceConfigureItem extends Item
{

    /**
     *
     * @return string
     */
    public function get_section()
    {
        return \Chamilo\Core\Repository\Manager :: SECTION_WORKSPACE;
    }

    /**
     *
     * @see \Chamilo\Core\Menu\Storage\DataClass\Item::is_selected()
     */
    public function is_selected()
    {
        $current_section = Page :: getInstance()->getSection();
        if ($current_section == $this->get_section())
        {
            return true;
        }
        return false;
    }
}
