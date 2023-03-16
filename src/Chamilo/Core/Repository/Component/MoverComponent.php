<?php
namespace Chamilo\Core\Repository\Component;

use Chamilo\Core\Repository\Filter\FilterData;
use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataClass\RepositoryCategory;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Core\Repository\Workspace\PersonalWorkspace;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\WorkspaceContentObjectRelation;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * Repository manager component to move objects between categories in the repository.
 *
 * @package repository.lib.repository_manager.component
 */
class MoverComponent extends Manager
{

    /**
     * Get all categories from which a user can select a target category when moving objects.
     *
     * @param array $exclude An array of category-id's which should be excluded from the resulting list.
     *
     * @return array A list of possible categories from which a user can choose. Can be used as input for a QuickForm
     *         select field.
     */
    private $level = 1;

    private $tree = [];

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $ids = $this->getRequest()->get(self::PARAM_CONTENT_OBJECT_ID);
        $this->set_parameter(self::PARAM_CONTENT_OBJECT_ID, $ids);

        if (!empty($ids))
        {
            if (!is_array($ids))
            {
                $ids = [$ids];
            }

            $object = DataManager::retrieve_by_id(ContentObject::class, $ids[0]);
            $parent = $object->get_parent_id();

            $this->tree = [];
            if ($parent != 0)
            {
                $this->tree[] = Translation::get('Repository');
            }

            $this->get_categories_for_select(0, $parent);
            $form = new FormValidator(
                'move', FormValidator::FORM_METHOD_POST, $this->get_url([self::PARAM_CONTENT_OBJECT_ID => $ids])
            );
            $form->addElement(
                'select', self::PARAM_DESTINATION_CONTENT_OBJECT_ID, Translation::get('NewCategory'), $this->tree
            );
            $form->addElement('submit', 'submit', Translation::get('Move', null, StringUtilities::LIBRARIES));

            if ($form->validate())
            {
                $destination = $form->exportValue(self::PARAM_DESTINATION_CONTENT_OBJECT_ID);
                $failures = 0;

                foreach ($ids as $id)
                {
                    $object = DataManager::retrieve_by_id(ContentObject::class, $id);

                    if ($this->getWorkspaceRightsService()->canEditContentObject(
                        $this->get_user(), $object, $this->getWorkspace()
                    ))
                    {

                        $versions = DataManager::get_version_ids($object);

                        foreach ($versions as $version)
                        {
                            /** @var ContentObject $object */
                            $object = DataManager::retrieve_by_id(ContentObject::class, $version);

                            if ($this->getWorkspace() instanceof PersonalWorkspace)
                            {
                                if (!$object->move($destination))
                                {
                                    $failures ++;
                                }
                            }
                            else
                            {
                                $contentObjectRelationService = $this->getContentObjectRelationService();
                                $contentObjectRelation =
                                    $contentObjectRelationService->getContentObjectRelationForWorkspaceAndContentObject(
                                        $this->getWorkspace(), $object
                                    );

                                if ($contentObjectRelation instanceof WorkspaceContentObjectRelation)
                                {
                                    if (!$contentObjectRelationService->updateContentObjectRelationFromParameters(
                                        $contentObjectRelation, $this->getWorkspace()->getId(),
                                        $object->get_object_number(), $destination
                                    ))
                                    {
                                        $failures ++;
                                    }
                                }
                                else
                                {
                                    if (!$contentObjectRelationService->createContentObjectRelationFromParameters(
                                        $this->getWorkspace()->getId(), $object->get_object_number(), $destination
                                    ))
                                    {
                                        $failures ++;
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        $failures ++;
                    }
                }

                // TODO: SCARA - Correct to reflect possible version errors
                if ($failures)
                {
                    if (count($ids) == 1)
                    {
                        $message = Translation::get(
                            'ObjectNotMoved', ['OBJECT' => Translation::get('ContentObject')],
                            StringUtilities::LIBRARIES
                        );
                    }
                    else
                    {
                        $message = Translation::get(
                            'ObjectsNotMoved', ['OBJECTS' => Translation::get('ContentObjects')],
                            StringUtilities::LIBRARIES
                        );
                    }
                }
                else
                {
                    if (count($ids) == 1)
                    {
                        $message = Translation::get(
                            'ObjectMoved', ['OBJECT' => Translation::get('ContentObject')], StringUtilities::LIBRARIES
                        );
                    }
                    else
                    {
                        $message = Translation::get(
                            'ObjectsMoved', ['OBJECTS' => Translation::get('ContentObjects')],
                            StringUtilities::LIBRARIES
                        );
                    }
                }

                $parameters = [];
                $parameters[Application::PARAM_ACTION] = self::ACTION_BROWSE_CONTENT_OBJECTS;
                $parameters[FilterData::FILTER_CATEGORY] = $object->get_parent_id();
                $this->redirectWithMessage($message, (bool) $failures, $parameters);
            }
            else
            {
                $html = [];

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
                        'NoObjectSelected', ['OBJECT' => Translation::get('ContentObject')], StringUtilities::LIBRARIES
                    )
                )
            );
        }
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url([self::PARAM_ACTION => self::ACTION_BROWSE_CONTENT_OBJECTS]),
                Translation::get('BrowserComponent')
            )
        );
    }

    protected function getContentObjectRelationService(): ContentObjectRelationService
    {
        return $this->getService(ContentObjectRelationService::class);
    }

    private function get_categories_for_select($parent_id, $current_parent)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_PARENT),
            new StaticConditionVariable($parent_id)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE_ID),
            new StaticConditionVariable($this->getWorkspace()->getId())
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(RepositoryCategory::class, RepositoryCategory::PROPERTY_TYPE),
            new StaticConditionVariable($this->getWorkspace()->getWorkspaceType())
        );

        $condition = new AndCondition($conditions);

        $categories = DataManager::retrieve_categories($condition);

        $tree = [];
        foreach ($categories as $cat)
        {
            $this->tree[$cat->get_id()] = str_repeat('--', $this->level) . ' ' . $cat->get_name();

            if ($current_parent == $cat->get_id())
            {
                $this->tree[$cat->get_id()] .= ' (' . Translation::get('Current', null, StringUtilities::LIBRARIES) .
                    ')';
            }

            $this->level ++;
            $this->get_categories_for_select($cat->get_id(), $current_parent);
            $this->level --;
        }
    }
}
