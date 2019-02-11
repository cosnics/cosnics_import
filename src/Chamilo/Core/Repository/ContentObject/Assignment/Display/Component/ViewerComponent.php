<?php

namespace Chamilo\Core\Repository\ContentObject\Assignment\Display\Component;

use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Service\RightsService;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Table\Entity\EntityTable;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Table\Entity\EntityTableParameters;
use Chamilo\Core\Repository\ContentObject\Assignment\Storage\DataClass\Assignment;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\Utilities;

/**
 *
 * @package Chamilo\Core\Repository\ContentObject\Assignment\Display\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class ViewerComponent extends Manager implements TableSupport
{

    /**
     *
     * @var ButtonToolBarRenderer
     */
    private $buttonToolbarRenderer;

    /**
     * @return string
     *
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NotAllowedException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function run()
    {
        $this->checkAccessRights();

        return $this->getTwig()->render(
            Manager::context() . ':EntityBrowser.html.twig', $this->getTemplateProperties()
        );
    }

    /**
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NotAllowedException
     */
    protected function checkAccessRights()
    {
        if(!$this->getRightsService()->canUserViewEntityBrowser($this->getUser(), $this->getAssignment()))
        {
            throw new NotAllowedException();
        }

    }

    /**
     *
     * @return string[]
     */
    protected function getTemplateProperties()
    {
        $entityName = $this->getAssignmentServiceBridge()->getPluralEntityNameByType($this->getEntityType());
        $entryCount = $this->getAssignmentServiceBridge()->countDistinctEntriesByEntityType($this->getEntityType());
        $feedbackCount = $this->getFeedbackServiceBridge()->countDistinctFeedbackByEntityType($this->getEntityType());
        $lateEntryCount = $this->getAssignmentServiceBridge()->countDistinctLateEntriesByEntityType($this->getEntityType());
        $entityCount = $this->getAssignmentServiceBridge()->countEntitiesByEntityType($this->getEntityType());

        /** @var Assignment $assignment */
        $assignment = $this->get_root_content_object();

        $startTime = DatetimeUtilities::format_locale_date(
            Translation::get('DateTimeFormatLong', null, Utilities::COMMON_LIBRARIES),
            $assignment->get_start_time()
        );

        $endTime = DatetimeUtilities::format_locale_date(
            Translation::get('DateTimeFormatLong', null, Utilities::COMMON_LIBRARIES),
            $assignment->get_end_time()
        );

        $notificationsUrl = $this->get_url(
            [
                self::PARAM_ACTION => self::ACTION_AJAX,
                \Chamilo\Core\Repository\ContentObject\Assignment\Display\Ajax\Manager::PARAM_ACTION => \Chamilo\Core\Repository\ContentObject\Assignment\Display\Ajax\Manager::ACTION_GET_NOTIFICATIONS
            ]
        );

        $notificationsCount = $this->getNotificationServiceBridge()->countUnseenNotificationsForUser($this->getUser());

        $redirect = new Redirect(
            [
                Application::PARAM_CONTEXT => \Chamilo\Core\Notification\Manager::context(),
                Application::PARAM_ACTION => \Chamilo\Core\Notification\Manager::ACTION_VIEW_NOTIFICATION,
                \Chamilo\Core\Notification\Manager::PROPERTY_NOTIFICATION_ID => '__NOTIFICATION_ID__'
            ]
        );

        $viewNotificationUrl = $redirect->getUrl();

        return [
            'HEADER' => $this->render_header(),
            'FOOTER' => $this->render_footer(),
            'BUTTON_TOOLBAR' => $this->getButtonToolbarRenderer()->render(),
            'CONTENT_OBJECT_TITLE' => $this->get_root_content_object()->get_title(),
            'CONTENT_OBJECT_RENDITION' => $this->renderContentObject(),
            'ENTITY_NAME' => $entityName, 'ENTITY_COUNT' => $entityCount, 'ENTRY_COUNT' => $entryCount,
            'FEEDBACK_COUNT' => $feedbackCount, 'LATE_ENTRY_COUNT' => $lateEntryCount,
            'START_TIME' => $startTime, 'END_TIME' => $endTime,
            'ALLOW_LATE_SUBMISSIONS' => $assignment->get_allow_late_submissions(),
            'VISIBILITY_SUBMISSIONS' => $assignment->get_visibility_submissions(),
            'ENTITY_TABLE' => $this->renderEntityTable(),
            'CAN_EDIT_ASSIGNMENT' => $this->getAssignmentServiceBridge()->canEditAssignment(),
            'ADMINISTRATOR_EMAIL' => $this->getConfigurationConsulter()->getSetting(['Chamilo\Core\Admin', 'administrator_email']),
            'NOTIFICATIONS_URL' => $notificationsUrl,
            'NOTIFICATIONS_COUNT' => $notificationsCount,
            'VIEW_NOTIFICATION_URL' => $viewNotificationUrl,
            'ADMIN_EMAIL' => $this->getConfigurationConsulter()->getSetting(['Chamilo\Core\Admin', 'administrator_email'])
        ];
    }

    /**
     *
     * @return string
     */
    protected function renderContentObject()
    {
        $display = ContentObjectRenditionImplementation::factory(
            $this->get_root_content_object(),
            ContentObjectRendition::FORMAT_HTML,
            ContentObjectRendition::VIEW_DESCRIPTION,
            $this
        );

        return $display->render();
    }

    /**
     *
     * @return string
     */
    protected function renderEntityTable()
    {
        $entityTableParameters = new EntityTableParameters();
        $entityTableParameters->setAssignmentServiceBridge($this->getAssignmentServiceBridge());
        $entityTableParameters->setFeedbackServiceBridge($this->getFeedbackServiceBridge());
        $entityTableParameters->setAssignment($this->getAssignment());
        $entityTableParameters->setEntityType($this->getEntityType());
        $entityTableParameters->setUser($this->getUser());
        $entityTableParameters->setRightService($this->getRightsService());

        return $this->getAssignmentServiceBridge()->getEntityTableForType($this, $entityTableParameters)->as_html();
    }

    /**
     *
     * @see \Chamilo\Libraries\Format\Table\Interfaces\TableSupport::get_table_condition()
     */
    public function get_table_condition($tableClassName)
    {
        // TODO Auto-generated method stub
    }

    protected function getButtonToolbarRenderer()
    {
        if (!isset($this->buttonToolbarRenderer))
        {
            $buttonToolBar = new ButtonToolBar();
            $buttonToolBar->addButtonGroup(
                new ButtonGroup(
                    array(
                        new Button(
                            Translation::get('DownloadAll'),
                            new FontAwesomeGlyph('download'),
                            $this->get_url([self::PARAM_ACTION => self::ACTION_DOWNLOAD])
                        ),
                    )
                )
            );

            if($this->isEphorusEnabled() && $this->getAssignmentServiceBridge()->canEditAssignment())
            {
                $buttonToolBar->addButtonGroup(
                    new ButtonGroup(
                        array(
                            new Button(
                                Translation::get('EphorusComponent'),
                                Theme::getInstance()->getImagePath('Chamilo\Application\Weblcms\Tool\Implementation\Ephorus', 'Logo/16'),
                                $this->get_url([self::PARAM_ACTION => self::ACTION_EPHORUS])
                            )
                        )
                    )
                );
            }

            $this->getExtensionManager()->buildButtonToolbarForEntityBrowser($this, $buttonToolBar);

            $this->buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolBar);
        }

        return $this->buttonToolbarRenderer;
    }
}
