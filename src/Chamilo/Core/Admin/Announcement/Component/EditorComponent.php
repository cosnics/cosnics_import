<?php
namespace Chamilo\Core\Admin\Announcement\Component;

use Chamilo\Core\Admin\Announcement\Form\PublicationForm;
use Chamilo\Core\Admin\Announcement\Manager;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package Chamilo\Core\Admin\Announcement\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class EditorComponent extends Manager
{

    /**
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NotAllowedException
     * @throws \Chamilo\Libraries\Rights\Exception\RightsLocationNotFoundException
     * @throws \QuickformException
     * @throws \Exception
     */
    public function run()
    {
        $this->checkAuthorization(Manager::CONTEXT, 'ManageChamilo');

        $translator = $this->getTranslator();

        $id = $this->getRequest()->query->get(self::PARAM_SYSTEM_ANNOUNCEMENT_ID);
        $this->set_parameter(self::PARAM_SYSTEM_ANNOUNCEMENT_ID, $id);

        if ($id)
        {
            $publication = $this->getPublicationService()->findPublicationByIdentifier($id);

            $content_object = $publication->get_content_object();

            $form = ContentObjectForm::factory(
                ContentObjectForm::TYPE_EDIT, $this->getCurrentWorkspace(), $content_object, 'edit',
                FormValidator::FORM_METHOD_POST, $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_EDIT,
                    self::PARAM_SYSTEM_ANNOUNCEMENT_ID => $publication->getId()
                ]
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
                    PublicationForm::TYPE_UPDATE, $this->get_url(['validated' => 1]),
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

                    $this->redirectWithMessage(
                        $translator->trans(
                            $success ? 'ObjectUpdated' : 'ObjectNotUpdated',
                            ['OBJECT' => $translator->trans('SystemAnnouncementPublication', [], Manager::CONTEXT)],
                            StringUtilities::LIBRARIES
                        ), !$success, [self::PARAM_ACTION => self::ACTION_BROWSE]
                    );
                }
                else
                {
                    $html = [];

                    $html[] = $this->renderHeader();
                    $html[] = $publicationForm->render();
                    $html[] = $this->renderFooter();

                    return implode(PHP_EOL, $html);
                }
            }
            else
            {
                $html = [];

                $html[] = $this->renderHeader();
                $html[] = $form->toHtml();
                $html[] = $this->renderFooter();

                return implode(PHP_EOL, $html);
            }
        }
        else
        {
            return $this->display_error_page(
                htmlentities(
                    $translator->trans(
                        'NoObjectSelected',
                        ['OBJECT' => $translator->trans('SystemAnnouncement', [], Manager::CONTEXT)],
                        StringUtilities::LIBRARIES
                    )
                )
            );
        }
    }

    protected function getCurrentWorkspace(): Workspace
    {
        return $this->getService('Chamilo\Core\Repository\CurrentWorkspace');
    }
}
