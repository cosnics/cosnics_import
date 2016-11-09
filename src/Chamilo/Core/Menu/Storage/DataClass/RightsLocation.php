<?php
namespace Chamilo\Core\Menu\Storage\DataClass;

use Chamilo\Core\Menu\Storage\DataManager;

/**
 *
 * @package Chamilo\Core\Menu\Storage\DataClass
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class RightsLocation extends \Chamilo\Core\Rights\RightsLocation
{

    public function get_data_manager()
    {
        return DataManager :: getInstance();
    }
}
