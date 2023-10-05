<?php
namespace Chamilo\Application\Weblcms\Request\Rights\Component;

use Chamilo\Application\Weblcms\Request\Rights\Manager;
use Chamilo\Application\Weblcms\Request\Rights\Storage\DataClass\RightsLocationEntityRightGroup;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

class DeleterComponent extends Manager
{

    function run()
    {
        if (! $this->getUser()->isPlatformAdmin())
        {
            throw new NotAllowedException();
        }
        
        $ids = $this->getRequest()->getFromRequestOrQuery(self::PARAM_LOCATION_ENTITY_RIGHT_GROUP_ID);
        $failures = 0;
        
        if (! empty($ids))
        {
            if (! is_array($ids))
            {
                $ids = array($ids);
            }
            
            foreach ($ids as $id)
            {
                $location_entity_right_group = DataManager::retrieve_by_id(
                    RightsLocationEntityRightGroup::class,
                    $id);
                
                if (! $location_entity_right_group->delete())
                {
                    $failures ++;
                }
            }
            
            if ($failures)
            {
                if (count($ids) == 1)
                {
                    $message = 'ObjectNotDeleted';
                    $parameter = array('OBJECT' => Translation::get('RightsLocationEntityRightGroup'));
                }
                elseif (count($ids) > $failures)
                {
                    $message = 'SomeObjectsNotDeleted';
                    $parameter = array('OBJECTS' => Translation::get('RightsLocationEntityRightGroups'));
                }
                else
                {
                    $message = 'ObjectsNotDeleted';
                    $parameter = array('OBJECTS' => Translation::get('RightsLocationEntityRightGroups'));
                }
            }
            else
            {
                if (count($ids) == 1)
                {
                    $message = 'ObjectDeleted';
                    $parameter = array('OBJECT' => Translation::get('RightsLocationEntityRightGroup'));
                }
                else
                {
                    $message = 'ObjectsDeleted';
                    $parameter = array('OBJECTS' => Translation::get('RightsLocationEntityRightGroups'));
                }
            }
            
            $this->redirectWithMessage(
                Translation::get($message, $parameter, StringUtilities::LIBRARIES), (bool) $failures,
                array(Manager::PARAM_ACTION => Manager::ACTION_BROWSE));
        }
        else
        {
            return $this->display_error_page(
                htmlentities(
                    Translation::get(
                        'NoObjectSelected', 
                        array('OBJECT' => Translation::get('RightsLocationEntityRightGroup')), 
                        StringUtilities::LIBRARIES)));
        }
    }
}