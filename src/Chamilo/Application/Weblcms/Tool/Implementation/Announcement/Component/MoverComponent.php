<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Announcement\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Announcement\Manager;
use Chamilo\Libraries\Platform\Session\Request;

class MoverComponent extends Manager
{

    public function get_move_direction()
    {
        return Request :: get(\Chamilo\Application\Weblcms\Tool\Manager :: PARAM_MOVE_DIRECTION);
    }
}
