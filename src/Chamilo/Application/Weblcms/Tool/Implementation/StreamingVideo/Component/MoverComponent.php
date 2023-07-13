<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\StreamingVideo\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\StreamingVideo\Manager;

class MoverComponent extends Manager
{

    public function get_move_direction()
    {
        return $this->getRequest()->query->get(\Chamilo\Application\Weblcms\Tool\Manager::PARAM_MOVE_DIRECTION);
    }
}
