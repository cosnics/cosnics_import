<?php
namespace Chamilo\Core\Repository\Table\ContentObject\Table;

use Chamilo\Core\Repository\Filter\FilterData;
use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Service\TemplateRegistrationConsulter;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Workspace\PersonalWorkspace;
use Chamilo\Core\Repository\Workspace\Service\RightsService;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTable;
use Chamilo\Libraries\Format\Table\FormAction\TableFormAction;
use Chamilo\Libraries\Format\Table\FormAction\TableFormActions;
use Chamilo\Libraries\Format\Table\Interfaces\TableFormActionsSupport;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 *
 * @package Chamilo\Core\Repository\Table\ContentObject\Table
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 */
class RepositoryTable extends DataClassTable implements TableFormActionsSupport
{
    const TABLE_IDENTIFIER = Manager::PARAM_CONTENT_OBJECT_ID;

    private $type;

    public function __construct($component)
    {
        parent::__construct($component);
        $template_id =
            FilterData::getInstance($this->get_component()->get_repository_browser()->getWorkspace())->getType();

        if (!$template_id || !is_numeric($template_id))
        {
            $this->type = ContentObject::class;
        }
        else
        {
            $template_registration =
                $this->getTemplateRegistrationConsulter()->getTemplateRegistrationByIdentifier($template_id);
            $this->type = $template_registration->get_content_object_type() . '\Storage\DataClass\\' .
                ClassnameUtilities::getInstance()->getPackageNameFromNamespace(
                    $template_registration->get_content_object_type()
                );
        }
    }

    /**
     * @return \Chamilo\Core\Repository\Service\TemplateRegistrationConsulter
     * @throws \Exception
     */
    public function getTemplateRegistrationConsulter()
    {
        return DependencyInjectionContainerBuilder::getInstance()->createContainer()->get(
            TemplateRegistrationConsulter::class
        );
    }

    public function get_implemented_form_actions(): TableFormActions
    {
        $actions = new TableFormActions(__NAMESPACE__, self::TABLE_IDENTIFIER);

        if ($this->get_component()->get_repository_browser()->getWorkspace() instanceof PersonalWorkspace)
        {
            $actions->add_form_action(
                new TableFormAction(
                    $this->get_component()->get_url(
                        array(Manager::PARAM_ACTION => Manager::ACTION_IMPACT_VIEW_RECYCLE)
                    ), Translation::get('RemoveSelected', null, StringUtilities::LIBRARIES), false
                )
            );
            $actions->add_form_action(
                new TableFormAction(
                    $this->get_component()->get_url(
                        array(Manager::PARAM_ACTION => Manager::ACTION_UNLINK_CONTENT_OBJECTS)
                    ), Translation::get('UnlinkSelected', null, StringUtilities::LIBRARIES)
                )
            );
        }

        $actions->add_form_action(
            new TableFormAction(
                $this->get_component()->get_url(array(Manager::PARAM_ACTION => Manager::ACTION_MOVE_CONTENT_OBJECTS)),
                Translation::get('MoveSelected', null, StringUtilities::LIBRARIES), false
            )
        );
        $actions->add_form_action(
            new TableFormAction(
                $this->get_component()->get_url(
                    array(
                        Manager::PARAM_ACTION => Manager::ACTION_PUBLICATION,
                        \Chamilo\Core\Repository\Publication\Manager::PARAM_ACTION => \Chamilo\Core\Repository\Publication\Manager::ACTION_PUBLISH
                    )
                ), Translation::get('PublishSelected', null, StringUtilities::LIBRARIES), false
            )
        );
        $actions->add_form_action(
            new TableFormAction(
                $this->get_component()->get_url(array(Manager::PARAM_ACTION => Manager::ACTION_EXPORT_CONTENT_OBJECTS)),
                Translation::get('ExportSelected', null, StringUtilities::LIBRARIES), false
            )
        );

        if ($this->get_component()->get_repository_browser()->getWorkspace() instanceof PersonalWorkspace)
        {
            $actions->add_form_action(
                new TableFormAction(
                    $this->get_component()->get_url(
                        array(
                            Application::PARAM_ACTION => Manager::ACTION_WORKSPACE,
                            \Chamilo\Core\Repository\Workspace\Manager::PARAM_ACTION => \Chamilo\Core\Repository\Workspace\Manager::ACTION_SHARE
                        )
                    ), Translation::get('ShareSelected', null, Manager::context()), false
                )
            );
        }
        else
        {
            $rightsService = RightsService::getInstance();
            $canDelete = $rightsService->canDeleteContentObjects(
                $this->get_component()->get_repository_browser()->getUser(),
                $this->get_component()->get_repository_browser()->getWorkspace()
            );

            if ($canDelete)
            {
                $actions->add_form_action(
                    new TableFormAction(
                        $this->get_component()->get_url(
                            array(
                                Application::PARAM_ACTION => Manager::ACTION_WORKSPACE,
                                \Chamilo\Core\Repository\Workspace\Manager::PARAM_ACTION => \Chamilo\Core\Repository\Workspace\Manager::ACTION_UNSHARE
                            )
                        ), Translation::get('UnshareSelected', null, Manager::context()), false
                    )
                );
            }
        }

        return $actions;
    }

    public function get_type()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @deprecated Use RepositoryTable::setType()
     */
    public function set_type($type)
    {
        $this->setType($type);
    }
}
