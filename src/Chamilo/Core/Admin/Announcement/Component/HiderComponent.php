<?php
namespace Chamilo\Core\Admin\Announcement\Component;

use Chamilo\Core\Admin\Announcement\Manager;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\Utilities;

class HiderComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkAuthorization(Manager::context(), 'ManageChamilo');

        $ids = $this->getRequest()->query->get(self::PARAM_SYSTEM_ANNOUNCEMENT_ID);
        $this->set_parameter(self::PARAM_SYSTEM_ANNOUNCEMENT_ID, $ids);

        $failures = 0;

        if (!empty($ids))
        {
            if (!is_array($ids))
            {
                $ids = array($ids);
            }

            foreach ($ids as $id)
            {
                $publication = $this->getPublicationService()->findPublicationByIdentifier((int) $id);
                $publication->toggle_visibility();

                if (!$this->getPublicationService()->updatePublication($publication))
                {
                    $failures ++;
                }
            }

            if ($failures)
            {
                if (count($ids) == 1)
                {
                    $message = 'ContentObjectNotToggled';
                    $parameter = array('OBJECT' => 'PublicationVisibility');
                }
                else
                {
                    $message = 'ContentObjectsNotToggled';
                    $parameter = array('OBJECTS' => 'PublicationsVisibility');
                }
            }
            else
            {
                if (count($ids) == 1)
                {
                    $message = 'ContentObjectToggled';
                    $parameter = array('OBJECT' => 'PublicationsVisibility');
                }
                else
                {
                    $message = 'ContentObjectsToggled';
                    $parameter = array('OBJECTS' => 'PublicationsVisibility');
                }
            }

            $this->redirect(
                Translation::get($message, $parameter, Utilities::COMMON_LIBRARIES), ($failures ? true : false),
                array(self::PARAM_ACTION => self::ACTION_BROWSE)
            );
        }
        else
        {
            return $this->display_error_page(
                htmlentities(
                    Translation::get(
                        'NoObjectSelected', array('OBJECT' => 'Publication'), Utilities::COMMON_LIBRARIES
                    )
                )
            );
        }
    }

    /**
     * @return \Chamilo\Core\Admin\Core\BreadcrumbGenerator
     */
    public function get_breadcrumb_generator()
    {
        return new \Chamilo\Core\Admin\Core\BreadcrumbGenerator($this, BreadcrumbTrail::getInstance());
    }
}
