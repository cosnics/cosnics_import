<?php
namespace Chamilo\Core\Repository\ContentObject\Assignment\Display\Component;

use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\FeedbackRightsServiceBridge;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\FeedbackServiceBridge;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\Interfaces\FeedbackServiceBridgeInterface;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\Storage\DataClass\Entry;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\Storage\DataClass\Score;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Form\ScoreFormType;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\FormHandler\SetScoreFormHandler;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Service\EntryNavigator;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Service\ScoreService;
use Chamilo\Core\Repository\Feedback\FeedbackSupport;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Core\User\Service\UserService;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationConfigurationInterface;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\DropdownButton;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\ActionBar\SplitDropdownButton;
use Chamilo\Libraries\Format\Structure\ActionBar\SubButton;
use Chamilo\Libraries\Format\Structure\ActionBar\SubButtonHeader;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\StringUtilities;
use Exception;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;

/**
 * @package Chamilo\Core\Repository\ContentObject\Assignment\Display\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class EntryComponent extends Manager implements FeedbackSupport
{

    /**
     * @var EntryNavigator
     */
    protected $entryNavigator;

    /**
     * @var Score
     */
    protected $score;

    /**
     * @var ScoreService
     */
    protected $scoreService;

    /**
     * @var ButtonToolBarRenderer
     */
    private $buttonToolbarRenderer;

    public function __construct(ApplicationConfigurationInterface $applicationConfiguration)
    {
        parent::__construct($applicationConfiguration);

        $this->scoreService = new ScoreService($this->getDataProvider());
    }

    /**
     * @return string
     * @throws NotAllowedException
     * @throws \Exception
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function run()
    {
        $this->initializeEntry();
        $this->checkAccessRights();

        $scoreForm = $this->getScoreForm();
        $this->processSubmittedData($scoreForm);

        return $this->getTwig()->render(
            Manager::CONTEXT . ':EntryViewer.html.twig', $this->getTemplateProperties($scoreForm)
        );
    }

    protected function buildBridgeServices()
    {
        /** @var FeedbackServiceBridgeInterface $assignmentFeedbackServiceBridge */
        $assignmentFeedbackServiceBridge =
            $this->getBridgeManager()->getBridgeByInterface(FeedbackServiceBridgeInterface::class);

        $feedbackServiceBridge = new FeedbackServiceBridge($assignmentFeedbackServiceBridge);
        $feedbackServiceBridge->setEntry($this->entry);

        $feedbackRightsServiceBridge = new FeedbackRightsServiceBridge();
        $feedbackRightsServiceBridge->setCurrentUser($this->getUser());

        $this->getBridgeManager()->addBridge($feedbackServiceBridge);
        $this->getBridgeManager()->addBridge($feedbackRightsServiceBridge);
    }

    /**
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NotAllowedException
     */
    protected function checkAccessRights()
    {
        if ($this->getEntry() &&
            $this->getRightsService()->canUserViewEntry($this->getUser(), $this->getAssignment(), $this->getEntry()))
        {
            return;
        }

        if ($this->getRightsService()->canUserViewEntity(
            $this->getUser(), $this->getAssignment(), $this->getEntityType(), $this->getEntityIdentifier()
        ))
        {
            return;
        }

        throw new NotAllowedException();
    }

    /**
     * @see \Chamilo\Core\Repository\Feedback\FeedbackSupport::count_feedbacks()
     */
    public function count_feedbacks()
    {
        return $this->getDataProvider()->countFeedbackByEntry($this->getEntry());
    }

    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = self::PARAM_ENTRY_ID;
        $additionalParameters[] = self::PARAM_ENTITY_ID;
        $additionalParameters[] = self::PARAM_ENTITY_TYPE;

        return parent::getAdditionalParameters($additionalParameters);
    }

    /**
     * @return array
     */
    protected function getAttachedContentObjects()
    {
        if (!$this->getEntry() instanceof Entry)
        {
            return [];
        }

        $entryAttachments = $this->getDataProvider()->findAttachmentsByEntry($this->getEntry());

        if (empty($entryAttachments))
        {
            return [];
        }

        $contentObjectAttachments = [];

        foreach ($entryAttachments as $entryAttachment)
        {
            /** @var ContentObject $contentObject */
            $contentObject = DataManager::retrieve_by_id(
                ContentObject::class, $entryAttachment->getAttachmentId()
            );

            $owner = $this->getUserService()->getUserFullNameByIdentifier($contentObject->get_owner_id());

            $contentObjectAttachments[] = [
                'attachment_id' => $entryAttachment->getId(),
                'content_object' => [
                    'id' => $contentObject->getId(),
                    'title' => $contentObject->get_title(),
                    'user' => $owner,
                    'date' => DatetimeUtilities::getInstance()->formatLocaleDate(
                        null, $contentObject->get_creation_date()
                    )
                ]
            ];
        }

        return $contentObjectAttachments;
    }

    protected function getButtonToolbarRenderer()
    {
        $buttonToolBar = new ButtonToolBar();

        if (!isset($this->actionBar))
        {

            $buttonGroup = new ButtonGroup();

            if ($this->getRightsService()->canUserCreateEntry(
                    $this->getUser(), $this->getAssignment(), $this->getEntityType(), $this->getEntityIdentifier()
                ) && $this->getAssignment()->canSubmit())
            {
                $buttonGroup->addButton(
                    new Button(
                        Translation::get('AddNewEntry'), new FontAwesomeGlyph('plus'), $this->get_url(
                        [
                            self::PARAM_ACTION => self::ACTION_CREATE,
                            self::PARAM_ENTITY_TYPE => $this->getEntityType(),
                            self::PARAM_ENTITY_ID => $this->getEntityIdentifier()
                        ]
                    ), Button::DISPLAY_ICON_AND_LABEL, null, ['btn-success']
                    )
                );
            }

            $buttonToolBar->addButtonGroup($buttonGroup);

            /*            if ($this->getDataProvider()->canEditAssignment())
                        {
                            $buttonToolBar->addButtonGroup(
                                new ButtonGroup(
                                    array(
                                        new Button(
                                            Translation::get(
                                                'CorrectCurrentEntry'
                                            ),
                                            new FontAwesomeGlyph('edit'),
                                            $this->get_url(
                                                [
                                                    self::PARAM_ACTION => self::ACTION_ENTRY_CODE_PAGE_CORRECTOR,
                                                    self::PARAM_ENTITY_TYPE => $this->getEntityType(),
                                                    self::PARAM_ENTITY_ID => $this->getEntityIdentifier(),
                                                    self::PARAM_ENTRY_ID => $this->getEntry()->getId()
                                                ]
                                            ),
                                            Button::DISPLAY_ICON_AND_LABEL,
                                            null,
                                            ['btn-success']
                                        )
                                    )
                                )
                            );
                        }*/

            $buttonGroup = new ButtonGroup();

            if ($this->getEntry() instanceof Entry)
            {
                $splitDropdownButton = new SplitDropdownButton(
                    Translation::get('DownloadAll'), new FontAwesomeGlyph('download'), $this->get_url(
                    [
                        self::PARAM_ACTION => self::ACTION_DOWNLOAD,
                        self::PARAM_ENTITY_TYPE => $this->getEntityType(),
                        self::PARAM_ENTITY_ID => $this->getEntityIdentifier()
                    ], [self::PARAM_ENTRY_ID]
                )
                );

                $splitDropdownButton->addSubButton(
                    new SubButton(
                        Translation::get('DownloadCurrent'), new FontAwesomeGlyph('download'), $this->get_url(
                        [
                            self::PARAM_ACTION => self::ACTION_DOWNLOAD,
                            self::PARAM_ENTITY_TYPE => $this->getEntityType(),
                            self::PARAM_ENTITY_ID => $this->getEntityIdentifier(),
                            self::PARAM_ENTRY_ID => $this->getEntry()->getId()
                        ]

                    )
                    )
                );

                $buttonGroup->addButton($splitDropdownButton);
            }

            $buttonToolBar->addButtonGroup($buttonGroup);

            if ($this->getDataProvider()->canEditAssignment() || $this->getAssignment()->get_visibility_submissions())
            {
                $buttonToolBar->addButtonGroup(
                    new ButtonGroup(
                        [
                            new Button(
                                Translation::get(
                                    'BrowseEntities', [
                                        'NAME' => strtolower(
                                            $this->getDataProvider()->getPluralEntityNameByType($this->getEntityType())
                                        )
                                    ]
                                ), new FontAwesomeGlyph('user'), $this->get_url(
                                [
                                    self::PARAM_ACTION => self::ACTION_VIEW
                                ], [self::PARAM_ENTRY_ID]
                            )
                            )
                        ]
                    )
                );
            }
        }

        $this->actionBar = new ButtonToolBarRenderer($buttonToolBar);

        return $this->actionBar;
    }

    /**
     * @return \Chamilo\Core\Repository\ContentObject\Assignment\Display\Service\EntryNavigator
     */
    protected function getEntryNavigator()
    {
        if (!isset($this->entryNavigator))
        {
            $this->entryNavigator = new EntryNavigator();
        }

        return $this->entryNavigator;
    }

    /**
     * @return \Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer
     */
    protected function getNavigatorButtonToolbarRenderer()
    {
        $buttonToolBar = new ButtonToolBar();

        if (!$this->getDataProvider()->canEditAssignment() || empty($this->getEntry()))
        {
            return new ButtonToolBarRenderer($buttonToolBar);
        }

        $currentEntityPosition = $this->getEntryNavigator()->getCurrentEntityPosition(
            $this->getDataProvider(), $this->getEntry(), $this->getEntityType(), $this->getEntityIdentifier()
        );

        $currentEntryPosition = $this->getEntryNavigator()->getCurrentEntryPosition(
            $this->getDataProvider(), $this->getEntry(), $this->getEntityType(), $this->getEntityIdentifier()
        );

        $translator = Translation::getInstance();
        $entityName = $this->getDataProvider()->getEntityNameByType($this->getEntityType());

        $entitiesCount = $this->getDataProvider()->countEntitiesWithEntriesByEntityType($this->getEntityType());
        $entriesCount = $this->getDataProvider()->countEntriesForEntityTypeAndId(
            $this->getEntityType(), $this->getEntityIdentifier()
        );

        if ($entitiesCount > 1)
        {
            $entityNavigatorActions = new ButtonGroup();

            $entityNavigatorActions->addButton(
                new Button(
                    $translator->getTranslation('PreviousEntity', ['ENTITY_NAME' => strtolower($entityName)]),
                    new FontAwesomeGlyph('backward'), $this->getPreviousEntityUrl(), ToolbarItem::DISPLAY_ICON_AND_LABEL
                )
            );

            $entityNavigatorActions->addButton(
                new Button(
                    '<span class="badge" style="color: white; background-color: #5bc0de; height: 19px; top: 0;">' .
                    $currentEntityPosition . ' / ' . $entitiesCount . '</span>', null, '#', ToolbarItem::DISPLAY_LABEL
                )
            );

            $entityNavigatorActions->addButton(
                new Button(
                    $translator->getTranslation('NextEntity', ['ENTITY_NAME' => strtolower($entityName)]),
                    new FontAwesomeGlyph('forward'), $this->getNextEntityUrl(), ToolbarItem::DISPLAY_ICON_AND_LABEL
                )
            );

            $buttonToolBar->addButtonGroup($entityNavigatorActions);

            $selectEntityButton = new DropdownButton(
                $this->getDataProvider()->renderEntityNameByEntityTypeAndEntityId(
                    $this->getEntityType(), $this->getEntityIdentifier()
                ), new FontAwesomeGlyph('user'), DropdownButton::DISPLAY_ICON_AND_LABEL, [], ['dropdown-entities']
            );

            $selectEntityButton->addSubButton(
                new SubButtonHeader(
                    $translator->getTranslation('SelectOtherEntity', ['ENTITY_TYPE' => strtolower($entityName)])
                )
            );

            $buttonToolBar->addItem($selectEntityButton);

            $entities = $this->getEntryNavigator()->getEntities();
            foreach ($entities as $entity)
            {
                if ($entity->getId() == $this->getEntityIdentifier())
                {
                    $isActive = true;
                    $url = '';
                }
                else
                {
                    $url = $this->get_url(
                        [self::PARAM_ENTITY_ID => $entity->getId()], [self::PARAM_ENTRY_ID]
                    );

                    $isActive = false;
                }

                $selectEntityButton->addSubButton(
                    new SubButton(
                        $this->getDataProvider()->renderEntityNameByEntityTypeAndEntity(
                            $this->getEntityType(), $entity
                        ), null, $url, SubButton::DISPLAY_LABEL, null, [], null, $isActive
                    )
                );
            }
        }

        if ($entriesCount > 1)
        {
            $entriesNavigatorActions = new ButtonGroup();

            $entriesNavigatorActions->addButton(
                new Button(
                    $translator->getTranslation('EarlierEntry'), new FontAwesomeGlyph('backward'),
                    $this->getPreviousEntryUrl(), ToolbarItem::DISPLAY_ICON_AND_LABEL
                )
            );

            $entriesNavigatorActions->addButton(
                new Button(
                    '<span class="badge" style="color: white; background-color: #28a745; height: 19px; top: 0;">' .
                    $currentEntryPosition . ' / ' . $entriesCount . '</span>', null, '#', ToolbarItem::DISPLAY_LABEL
                )
            );

            $entriesNavigatorActions->addButton(
                new Button(
                    $translator->getTranslation('LaterEntry'), new FontAwesomeGlyph('forward'),
                    $this->getNextEntryUrl(), ToolbarItem::DISPLAY_ICON_AND_LABEL
                )
            );

            $buttonToolBar->addButtonGroup($entriesNavigatorActions);
        }

        return new ButtonToolBarRenderer($buttonToolBar);
    }

    /**
     * @return string
     */
    protected function getNextEntityUrl()
    {
        $nextEntity = $this->getEntryNavigator()->getNextEntity(
            $this->getDataProvider(), $this->getEntry(), $this->getEntityType(), $this->getEntityIdentifier()
        );

        if (!$nextEntity instanceof DataClass)
        {
            return null;
        }

        return $this->get_url(
            [self::PARAM_ENTITY_ID => $nextEntity->getId()], [self::PARAM_ENTRY_ID]
        );
    }

    /**
     * @return string
     */
    protected function getNextEntryUrl()
    {
        $nextEntry = $this->getEntryNavigator()->getNextEntry(
            $this->getDataProvider(), $this->getEntry(), $this->getEntityType(), $this->getEntityIdentifier()
        );

        if (!$nextEntry instanceof Entry)
        {
            return null;
        }

        return $this->get_url([self::PARAM_ENTRY_ID => $nextEntry->getId()]);
    }

    /**
     * @return string
     */
    protected function getPreviousEntityUrl()
    {
        $previousEntity = $this->getEntryNavigator()->getPreviousEntity(
            $this->getDataProvider(), $this->getEntry(), $this->getEntityType(), $this->getEntityIdentifier()
        );

        if (!$previousEntity instanceof DataClass)
        {
            return null;
        }

        return $this->get_url(
            [self::PARAM_ENTITY_ID => $previousEntity->getId()], [self::PARAM_ENTRY_ID]
        );
    }

    /**
     * @return string
     */
    protected function getPreviousEntryUrl()
    {
        $previousEntry = $this->getEntryNavigator()->getPreviousEntry(
            $this->getDataProvider(), $this->getEntry(), $this->getEntityType(), $this->getEntityIdentifier()
        );

        if (!$previousEntry instanceof Entry)
        {
            return null;
        }

        return $this->get_url([self::PARAM_ENTRY_ID => $previousEntry->getId()]);
    }

    /**
     * @return int|null
     */
    protected function getScore()
    {
        if (!is_null($this->score))
        {
            return $this->score->getScore();
        }
        else
        {
            return $this->getScoreDataClass() instanceof Score ? $this->getScoreDataClass()->getScore() : null;
        }
    }

    /**
     * @return Score
     */
    protected function getScoreDataClass()
    {
        if (!$this->getEntry() instanceof Entry)
        {
            return null;
        }

        return $this->getDataProvider()->findScoreByEntry($this->getEntry());
    }

    /**
     * @return Form|\Symfony\Component\Form\FormInterface
     */
    protected function getScoreForm()
    {
        $this->score = $this->getScoreDataClass();

        if (empty($this->score))
        {
            $this->score = $this->getDataProvider()->initializeScore();

            if ($this->getEntry() instanceof Entry)
            {
                $this->score->setEntryId($this->getEntry()->getId());
            }
        }

        $formFactory = $this->getForm();

        return $formFactory->create(ScoreFormType::class, $this->score);
    }

    /**
     * @param FormInterface $scoreForm
     *
     * @return array|string[]
     */
    protected function getTemplateProperties(FormInterface $scoreForm = null)
    {
        /** @var \Chamilo\Core\Repository\ContentObject\Assignment\Storage\DataClass\Assignment $assignment */
        $assignment = $this->get_root_content_object();

        $baseParameters = [
            'HAS_ENTRY' => false,
            'IS_USER_PART_OF_ENTITY' => $this->getDataProvider()->isUserPartOfEntity(
                $this->getUser(), $this->getEntityType(), $this->getEntityIdentifier()
            ),
            'CHANGE_ENTITY_URL' => $this->get_url([self::PARAM_ENTITY_ID => '__ENTITY_ID__']),
            'HEADER' => $this->render_header(),
            'FOOTER' => $this->render_footer(),
            'BUTTON_TOOLBAR' => $this->getButtonToolbarRenderer()->render(),
            'NAVIGATOR_BUTTON_TOOLBAR' => $this->getNavigatorButtonToolbarRenderer()->render(),
            'ASSIGNMENT_TITLE' => $this->get_root_content_object()->get_title(),
            'ASSIGNMENT_RENDITION' => $this->renderAssignment(),
            'ATTACHMENT_VIEWER_URL' => $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_VIEW_ATTACHMENT,
                    self::PARAM_ATTACHMENT_ID => '__ATTACHMENT_ID__'
                ]
            )
        ];

        $baseParameters = $this->getAvailableEntitiesParameters($baseParameters);

        if (!$this->getEntry() instanceof Entry)
        {
            return $baseParameters;
        }

        $dateFormat = Translation::get('DateTimeFormatLong', null, StringUtilities::LIBRARIES);
        $submittedDate =
            DatetimeUtilities::getInstance()->formatLocaleDate($dateFormat, $this->getEntry()->getSubmitted());

        $configuration = new ApplicationConfiguration($this->getRequest(), $this->getUser(), $this);
        $configuration->set(\Chamilo\Core\Repository\Feedback\Manager::CONFIGURATION_SHOW_FEEDBACK_HEADER, false);

        $this->buildBridgeServices();

        $feedbackManager = $this->getApplicationFactory()->getApplication(
            'Chamilo\Core\Repository\Feedback', $configuration, \Chamilo\Core\Repository\Feedback\Manager::ACTION_BROWSE
        );

        $feedbackManagerHtml = $feedbackManager->run();

        $contentObjects = $assignment->getAutomaticFeedbackObjects();

        $extendParameters = [
            'HAS_ENTRY' => true,
            'CONTENT_OBJECT_TITLE' => $this->getEntry()->getContentObject() ?
                $this->getEntry()->getContentObject()->get_title() :
                Translation::getInstance()->getTranslation('SubmissionRemoved', null, Manager::CONTEXT),
            'CONTENT_OBJECT_RENDITION' => $this->getEntry()->getContentObject() ? $this->renderContentObject() : null,
            'FEEDBACK_MANAGER' => $feedbackManagerHtml,
            'FEEDBACK_COUNT' => $this->count_feedbacks(),
            'SUBMITTED_DATE' => $submittedDate,
            'SUBMITTED_BY' => $this->getUserService()->getUserFullNameByIdentifier($this->getEntry()->getUserId()),
            'SCORE_FORM' => $scoreForm->createView(),
            'SCORE' => $this->getScore(),
            'CAN_EDIT_ASSIGNMENT' => $this->getDataProvider()->canEditAssignment(),
            'ENTRY_TABLE' => $this->renderEntryTable(),
            'ENTRY_COUNT' => $this->getDataProvider()->countEntriesForEntityTypeAndId(
                $this->getEntityType(), $this->getEntityIdentifier()
            ),
            'SHOW_AUTOMATIC_FEEDBACK' => $assignment->isAutomaticFeedbackVisible(),
            'AUTOMATIC_FEEDBACK_TEXT' => $assignment->get_automatic_feedback_text(),
            'AUTOMATIC_FEEDBACK_CONTENT_OBJECTS' => $contentObjects,
            'UPLOAD_ENTRY_ATTACHMENT_URL' => $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_AJAX,
                    \Chamilo\Core\Repository\ContentObject\Assignment\Display\Ajax\Manager::PARAM_ACTION => \Chamilo\Core\Repository\ContentObject\Assignment\Display\Ajax\Manager::ACTION_UPLOAD_ENTRY_ATTACHMENT,
                    self::PARAM_ENTRY_ID => $this->getEntry()->getId()
                ]
            ),
            'DELETE_ENTRY_ATTACHMENT_URL' => $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_AJAX,
                    \Chamilo\Core\Repository\ContentObject\Assignment\Display\Ajax\Manager::PARAM_ACTION => \Chamilo\Core\Repository\ContentObject\Assignment\Display\Ajax\Manager::ACTION_DELETE_ENTRY_ATTACHMENT
                ]
            ),
            'ATTACHMENT_VIEWER_URL' => $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_VIEW_ATTACHMENT,
                    self::PARAM_ATTACHMENT_ID => '__ATTACHMENT_ID__',
                    self::PARAM_ENTRY_ID => $this->getEntry()->getId()
                ]
            ),
            'ATTACHED_CONTENT_OBJECTS' => $this->getAttachedContentObjects(),
            'SHOW_COMPACT_FEEDBACK' => $this->getConfigurationConsulter()->getSetting(
                ['Chamilo\Core\Repository\ContentObject\Assignment', 'show_compact_feedback']
            )
        ];

        return array_merge($baseParameters, $extendParameters);
    }

    /**
     * @return \Chamilo\Core\User\Service\UserService
     */
    protected function getUserService()
    {
        return $this->getService(UserService::class);
    }

    /**
     * @see \Chamilo\Core\Repository\Feedback\FeedbackSupport::get_feedback()
     */
    public function get_feedback()
    {
        $feedback = $this->getDataProvider()->initializeFeedback();
        $feedback->setEntryId($this->getEntry()->getId());

        return $feedback;
    }

    /**
     *
     */
    protected function initializeEntry()
    {
        try
        {
            parent::initializeEntry();
        }
        catch (Exception $ex)
        {
            $this->entry =
                $this->getDataProvider()->findLastEntryForEntity($this->getEntityType(), $this->getEntityIdentifier());
        }

        if (!$this->entry instanceof Entry)
        {
            $breadcrumbTrail = $this->getBreadcrumbTrail();
            $breadcrumbTrail->getLast()->setName(
                Translation::getInstance()->getTranslation('ViewerComponent', null, Manager::CONTEXT)
            );
        }
    }

    /**
     * @see \Chamilo\Core\Repository\Feedback\FeedbackSupport::is_allowed_to_create_feedback()
     */
    public function is_allowed_to_create_feedback()
    {
        return true;
    }

    /**
     * @see \Chamilo\Core\Repository\Feedback\FeedbackSupport::is_allowed_to_delete_feedback()
     */
    public function is_allowed_to_delete_feedback($feedback)
    {
        return $feedback->get_user_id() == $this->getUser()->getId();
    }

    /**
     * @param \Chamilo\Core\Repository\Feedback\Storage\DataClass\Feedback $feedback
     *
     * @return bool
     */
    public function is_allowed_to_update_feedback($feedback)
    {
        return $feedback->get_user_id() == $this->getUser()->getId();
    }

    /**
     * @see \Chamilo\Core\Repository\Feedback\FeedbackSupport::is_allowed_to_view_feedback()
     */
    public function is_allowed_to_view_feedback()
    {
        return true;
    }

    /**
     * @param FormInterface $scoreForm
     *
     * @return bool
     * @throws \Exception
     */
    protected function processSubmittedData(FormInterface $scoreForm)
    {
        if (!$this->getDataProvider()->canEditAssignment() || !$this->getEntry() instanceof Entry)
        {
            return false;
        }

        $scoreFormHandler = new SetScoreFormHandler($this->scoreService);
        $scoreFormHandler->setScoringUser($this->getUser());

        $success = $scoreFormHandler->handle($scoreForm, $this->getRequest());
        if ($success)
        {
            $score = $scoreForm->getData();
            $this->getNotificationServiceBridge()->createNotificationForNewScore(
                $this->getUser(), $this->getEntry(), $score
            );
        }

        return $success;
    }

    /**
     * @return string
     */
    protected function renderAssignment()
    {
        $contentObject = $this->get_root_content_object();

        $display = ContentObjectRenditionImplementation::factory(
            $contentObject, ContentObjectRendition::FORMAT_HTML, ContentObjectRendition::VIEW_DESCRIPTION, $this
        );

        return $display->render();
    }

    /**
     * @return string
     */
    protected function renderContentObject()
    {
        $contentObject = $this->getEntry()->getContentObject();

        $display = ContentObjectRenditionImplementation::factory(
            $contentObject, ContentObjectRendition::FORMAT_HTML, ContentObjectRendition::VIEW_DESCRIPTION, $this
        );

        return $display->render();
    }

    /**
     * @return string
     */
    protected function renderEntryTable()
    {
        $table = $this->getDataProvider()->getEntryTableForEntityTypeAndId(
            $this, $this->getEntityType(), $this->getEntityIdentifier()
        );

        if (!empty($table))
        {
            return $table->render();
        }

        return '';
    }

    /**
     * @see \Chamilo\Core\Repository\Feedback\FeedbackSupport::retrieve_feedback()
     */
    public function retrieve_feedback($feedbackIdentifier)
    {
        return $this->getDataProvider()->findFeedbackByIdentifier($feedbackIdentifier);
    }

    /**
     * @see \Chamilo\Core\Repository\Feedback\FeedbackSupport::retrieve_feedbacks()
     */
    public function retrieve_feedbacks($count, $offset)
    {
        return $this->getDataProvider()->findFeedbackByEntry($this->getEntry());
    }
}
