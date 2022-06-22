<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Forum\Component;

use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Application\Weblcms\Tool\Implementation\Forum\Manager;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * this is the component for moving a forum in the list
 * 
 * @author Mattias De Pauw
 */
class MoverComponent extends Manager
{

    public function run()
    {
        if ($this->is_allowed(EDIT_RIGHT))
        {
            $move = 0;
            
            if (Request::get(\Chamilo\Application\Weblcms\Tool\Manager::PARAM_MOVE_DIRECTION))
            {
                $move = Request::get(\Chamilo\Application\Weblcms\Tool\Manager::PARAM_MOVE_DIRECTION);
            }
            
            $forum_publication = DataManager::retrieve_by_id(
                ContentObjectPublication::class,
                Request::get(self::PARAM_PUBLICATION_ID));
            
            if ($forum_publication->move($move))
            {
                $failure = false;
                $message = Translation::get(
                    'ObjectMoved', 
                    array('OBJECT' => Translation::get('Forum', null, 'Chamilo\Core\Repository\ContentObject\Forum')), 
                    StringUtilities::LIBRARIES);
            }
            else
            {
                $failure = true;
                $message = Translation::get(
                    'ObjectNotMoved', 
                    array('OBJECT' => Translation::get('Forum', null, 'Chamilo\Core\Repository\ContentObject\Forum')), 
                    StringUtilities::LIBRARIES);
            }
            
            $this->redirectWithMessage($message, $failure, array(self::PARAM_ACTION => self::ACTION_BROWSE));
        }
    }

    public function get_move_direction()
    {
        return Request::get(\Chamilo\Application\Weblcms\Tool\Manager::PARAM_MOVE_DIRECTION);
    }
}
