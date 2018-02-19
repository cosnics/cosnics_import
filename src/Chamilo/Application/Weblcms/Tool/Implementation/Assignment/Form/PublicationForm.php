<?php

namespace Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Form;

use Chamilo\Application\Weblcms\Form\ContentObjectPublicationForm;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\Assignment\Entry;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Storage\DataClass\Publication;
use Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Storage\DataManager;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Translation\Translation;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Form
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class PublicationForm extends ContentObjectPublicationForm
{
    /**
     * @var \Symfony\Component\Translation\Translator
     */
    protected $translator;

    /**
     *
     * @param \Chamilo\Core\User\Storage\DataClass\User $user
     * @param integer $form_type
     * @param ContentObjectPublication[] $publications
     * @param \Chamilo\Application\Weblcms\Course\Storage\DataClass\Course $course
     * @param string $action
     * @param boolean $is_course_admin
     * @param array $selectedContentObjects
     *
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException
     */
    public function __construct(
        User $user, $form_type, $publications, $course, $action, $is_course_admin,
        $selectedContentObjects = array(), Translator $translator
    )
    {
        $this->translator = $translator;

        parent::__construct(
            'Chamilo\Application\Weblcms\Tool\Implementation\Assignment',
            $user,
            $form_type,
            $publications,
            $course,
            $action,
            $is_course_admin,
            $selectedContentObjects
        );
    }

    /**
     * Builds the basic create form (without buttons)
     */
    public function build_basic_create_form()
    {
        $this->addElement('category', $this->translator->trans('DefaultProperties', [], Manager::context()));
        parent::build_basic_create_form();
        $this->addAssignmentProperties();
    }

    /**
     * Builds the basic update form (without buttons)
     */
    public function build_basic_update_form()
    {
        $this->addElement('category', $this->translator->trans('DefaultProperties', [], Manager::context()));
        parent::build_basic_update_form();
        $this->addAssignmentProperties();
    }

    protected function addAssignmentProperties()
    {
        $this->addElement('category', $this->translator->trans('AssignmentProperties', [], Manager::context()));

        $group[] = $this->createElement(
            'radio',
            null,
            null,
            $this->translator->trans('Users', [], Manager::context()),
            Entry::ENTITY_TYPE_USER
        );

        $group[] = $this->createElement(
            'radio',
            null,
            null,
            $this->translator->trans('CourseGroups', [], Manager::context()),
            Entry::ENTITY_TYPE_COURSE_GROUP
        );

        $group[] = $this->createElement(
            'radio',
            null,
            null,
            $this->translator->trans('PlatformGroups', [], Manager::context()),
            Entry::ENTITY_TYPE_PLATFORM_GROUP
        );

        $this->addGroup(
            $group,
            Publication::PROPERTY_ENTITY_TYPE,
            $this->translator->trans('PublishAssignmentForEntity', [], Manager::context()),
            ''
        );
    }

    /**
     * Handles the submit of the form for both create and edit
     *
     * @return boolean
     *
     * @throws \HTML_QuickForm_Error
     */
    public function handle_form_submit()
    {
        if(!parent::handle_form_submit())
        {
            return false;
        }

        $publications = $this->get_publications();
        $success = true;

        foreach ($publications as $publication)
        {
            if($this->get_form_type() == self::TYPE_CREATE)
            {
                $success &= $this->handleCreateAction($publication);
            }
            else
            {
                $success &= $this->handleUpdateAction($publication);
            }
        }

        return $success;
    }

    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $contentObjectPublication
     *
     * @return bool
     *
     * @throws \HTML_QuickForm_Error
     */
    protected function handleCreateAction(ContentObjectPublication $contentObjectPublication)
    {
        $exportValues = $this->exportValues();

        $publication = new Publication();

        $publication->setPublicationId($contentObjectPublication->getId());
        $publication->setEntityType($exportValues[Publication::PROPERTY_ENTITY_TYPE]);

        return $publication->create();
    }

    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $contentObjectPublication
     *
     * @return bool
     * @throws \HTML_QuickForm_Error
     */
    protected function handleUpdateAction(ContentObjectPublication $contentObjectPublication)
    {
        $exportValues = $this->exportValues();

        try
        {
            $publication = DataManager::getAssignmentPublicationByPublicationId($contentObjectPublication->getId());
            $publication->setEntityType($exportValues[Publication::PROPERTY_ENTITY_TYPE]);

            return $publication->update();
        }
        catch(\Exception $ex)
        {
            return false;
        }
    }
}