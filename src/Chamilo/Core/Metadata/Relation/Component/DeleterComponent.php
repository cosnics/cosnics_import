<?php
namespace Chamilo\Core\Metadata\Relation\Component;

use Chamilo\Core\Metadata\Schema\Manager;
use Chamilo\Core\Metadata\Schema\Storage\DataManager;
use Chamilo\Core\Metadata\Storage\DataClass\Schema;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;
use Exception;

/**
 * Controller to delete the schema
 * 
 * @package Chamilo\Core\Metadata\Schema\Component
 * @author Sven Vanpoucke - Hogeschool Gent
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class DeleterComponent extends Manager
{

    /**
     * Executes this controller
     */
    public function run()
    {
        if (! $this->get_user()->isPlatformAdmin())
        {
            throw new NotAllowedException();
        }
        
        $schema_ids = $this->getRequest()->getFromRequestOrQuery(self::PARAM_SCHEMA_ID);
        $this->set_parameter(self::PARAM_SCHEMA_ID, $schema_ids);
        
        try
        {
            if (empty($schema_ids))
            {
                throw new NoObjectSelectedException(Translation::get('Schema'));
            }
            
            if (! is_array($schema_ids))
            {
                $schema_ids = array($schema_ids);
            }
            
            foreach ($schema_ids as $schema_id)
            {
                $schema = DataManager::retrieve_by_id(Schema::class, $schema_id);
                
                if ($schema->is_fixed())
                {
                    throw new NotAllowedException();
                }
                
                if (! $schema->delete())
                {
                    throw new Exception(
                        Translation::get(
                            'ObjectNotDeleted', 
                            array('OBJECT' => Translation::get('Schema')), 
                            StringUtilities::LIBRARIES));
                }
            }
            
            $success = true;
            $message = Translation::get(
                'ObjectDeleted', 
                array('OBJECT' => Translation::get('Schema')), 
                StringUtilities::LIBRARIES);
        }
        catch (Exception $ex)
        {
            $success = false;
            $message = $ex->getMessage();
        }
        
        $this->redirectWithMessage($message, ! $success, array(self::PARAM_ACTION => self::ACTION_BROWSE));
    }

    /**
     * Adds additional breadcrumbs
     * 
     * @param \Chamilo\Libraries\Format\Structure\BreadcrumbTrail $breadcrumb_trail
     */
    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumb_trail)
    {
        $breadcrumb_trail->add(
            new Breadcrumb(
                $this->get_url(array(Manager::PARAM_ACTION => Manager::ACTION_BROWSE), array(self::PARAM_SCHEMA_ID)), 
                Translation::get('BrowserComponent')));
    }
}