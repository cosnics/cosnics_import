<?php
namespace Chamilo\Core\Admin\Announcement\Component;

use Chamilo\Core\Admin\Announcement\Form\PublicationForm;
use Chamilo\Core\Admin\Announcement\Manager;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Core\Repository\Workspace\PersonalWorkspace;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * @package Chamilo\Core\Admin\Announcement\Component
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class EditorComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkAuthorization(Manager::context(), 'ManageChamilo');

        $id = $this->getRequest()->query->get(self::PARAM_SYSTEM_ANNOUNCEMENT_ID);
        $this->set_parameter(self::PARAM_SYSTEM_ANNOUNCEMENT_ID, $id);

        if ($id)
        {
            $publication = $this->getPublicationService()->findPublicationByIdentifier((int) $id);

            $content_object = $publication->get_content_object();

            $form = ContentObjectForm::factory(
                ContentObjectForm::TYPE_EDIT, new PersonalWorkspace($this->get_user()), $content_object, 'edit', 'post',
                $this->get_url(
                    array(
                        self::PARAM_ACTION => self::ACTION_EDIT,
                        self::PARAM_SYSTEM_ANNOUNCEMENT_ID => $publication->get_id()
                    )
                )
            );

            if ($form->validate() || $this->getRequest()->query->get('validated'))
            {
                $form->update_content_object();

                if ($form->is_version())
                {
                    $publication->set_content_object_id($content_object->get_latest_version_id());
                    $this->getPublicationService()->updatePublication($publication);
                }

                $publicationForm = new PublicationForm(
                    PublicationForm::TYPE_UPDATE, $this->get_url(array('validated' => 1)),
                    $this->getRightsService()->getEntities()
                );

                $publicationForm->setPublicationDefaults(
                    $this->getUser(), $publication,
                    $this->getRightsService()->getViewTargetUsersAndGroupsIdentifiersForPublicationIdentifier(
                        $publication->getId()
                    )
                );

                if ($publicationForm->validate())
                {
                    $success = $this->getPublicationService()->savePublicationFromValues(
                        $publication, $this->getUser()->getId(), $publicationForm->exportValues()
                    );

                    $this->redirect(
                        Translation::get(
                            $success ? 'ObjectUpdated' : 'ObjectNotUpdated',
                            array('OBJECT' => Translation::get('SystemAnnouncementPublication')),
                            Utilities::COMMON_LIBRARIES
                        ), ($success ? false : true), array(self::PARAM_ACTION => self::ACTION_BROWSE)
                    );
                }
                else
                {
                    $html = array();

                    $html[] = $this->render_header();
                    $html[] = $publicationForm->render();
                    $html[] = $this->render_footer();

                    return implode(PHP_EOL, $html);
                }
            }
            else
            {
                $html = array();

                $html[] = $this->render_header();
                $html[] = $form->toHtml();
                $html[] = $this->render_footer();

                return implode(PHP_EOL, $html);
            }
        }
        else
        {
            return $this->display_error_page(
                htmlentities(
                    Translation::get(
                        'NoObjectSelected', array('OBJECT' => Translation::get('SystemAnnouncement')),
                        Utilities::COMMON_LIBRARIES
                    )
                )
            );
        }
    }
}
