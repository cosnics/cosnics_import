<?php

namespace Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Service;

use Chamilo\Application\Weblcms\Course\Storage\DataClass\Course;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Service\AssignmentService;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\Assignment\Entry;
use Chamilo\Application\Weblcms\Service\CourseService;
use Chamilo\Application\Weblcms\Service\PublicationService;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Service\Entity\EntityServiceManager;
use Chamilo\Core\Notification\Domain\TranslationContext;
use Chamilo\Core\Notification\Domain\ViewingContext;
use Chamilo\Core\Notification\Service\FilterManager;
use Chamilo\Core\Notification\Service\NotificationManager;
use Chamilo\Core\Notification\Storage\Entity\Filter;
use Chamilo\Core\Queue\Service\JobProcessorInterface;
use Chamilo\Core\Queue\Storage\Entity\Job;
use Chamilo\Core\Repository\ContentObject\Assignment\Storage\DataClass\Assignment;
use Chamilo\Core\Repository\Workspace\Repository\ContentObjectRepository;
use Chamilo\Core\User\Service\UserService;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\File\Redirect;

/**
 * @package Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Service
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class EntryNotificationJobProcessor implements JobProcessorInterface
{
    /**
     * @var \Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Service\AssignmentService
     */
    protected $assignmentService;

    /**
     * @var \Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Service\Entity\EntityServiceManager
     */
    protected $entityServiceManager;

    /**
     * @var \Chamilo\Application\Weblcms\Service\PublicationService
     */
    protected $publicationService;

    /**
     * @var \Chamilo\Application\Weblcms\Service\CourseService
     */
    protected $courseService;

    /**
     * @var \Chamilo\Core\User\Service\UserService
     */
    protected $userService;

    /**
     * @var \Chamilo\Core\Repository\Workspace\Repository\ContentObjectRepository
     */
    protected $contentObjectRepository;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @var NotificationManager
     */
    protected $notificationManager;

    /**
     * EntryNotificationProcessor constructor.
     *
     * @param \Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Service\AssignmentService $assignmentService
     * @param \Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Service\Entity\EntityServiceManager $entityServiceManager
     * @param \Chamilo\Application\Weblcms\Service\PublicationService $publicationService
     * @param \Chamilo\Application\Weblcms\Service\CourseService $courseService
     * @param \Chamilo\Core\User\Service\UserService $userService
     * @param \Chamilo\Core\Repository\Workspace\Repository\ContentObjectRepository $contentObjectRepository
     * @param FilterManager $filterManager
     * @param NotificationManager $notificationManager
     */
    public function __construct(
        AssignmentService $assignmentService, EntityServiceManager $entityServiceManager,
        PublicationService $publicationService, CourseService $courseService,
        UserService $userService, ContentObjectRepository $contentObjectRepository,
        FilterManager $filterManager, NotificationManager $notificationManager
    )
    {
        $this->assignmentService = $assignmentService;
        $this->entityServiceManager = $entityServiceManager;
        $this->publicationService = $publicationService;
        $this->courseService = $courseService;
        $this->userService = $userService;
        $this->contentObjectRepository = $contentObjectRepository;
        $this->filterManager = $filterManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param \Chamilo\Core\Queue\Storage\Entity\Job $job
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function processJob(Job $job)
    {
        /** @var \Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Domain\EntryNotificationJobParameters $jobParameters */
        $jobParameters = $job->getJobParameters();

        $entry = $this->assignmentService->findEntryByIdentifier($jobParameters->getEntryId());
        if (!$entry instanceof Entry)
        {
            throw new \InvalidArgumentException(
                sprintf('The given entry with id %s could not be found', $jobParameters->getEntryId())
            );
        }

        $publication = $this->publicationService->getPublication($entry->getContentObjectPublicationId());
        if (!$publication instanceof ContentObjectPublication)
        {

            throw new \InvalidArgumentException(
                sprintf(
                    'The given content object publication with id %s could not be found',
                    $entry->getContentObjectPublicationId()
                )
            );
        }

        $course = $this->courseService->getCourseById($publication->get_course_id());
        if (!$course instanceof Course)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    'The given course with id %s could not be found', $publication->get_course_id()
                )
            );
        }

        $assignment = $this->contentObjectRepository->findById($publication->get_content_object_id());
        if (!$assignment instanceof Assignment)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    'The given assignment with id %s could not be found', $publication->get_content_object_id()
                )
            );
        }

        $targetUserIds = $this->getTargetUserIds($course, $entry);
        $filters = $this->getFilters($publication, $course, $assignment);
        $url = $this->getNotificationUrl($course, $publication, $entry);
        $viewingContexts = $this->getNotificationViewingContexts($publication, $assignment, $course, $entry);

        $date = new \DateTime();
        $date->setTimestamp($entry->getSubmitted());

        $this->notificationManager->createNotificationForUsers(
            $url, $viewingContexts, $date, $targetUserIds, $filters
        );
    }

    /**
     * @param Course $course
     * @param Entry $entry
     *
     * @return int[]
     */
    protected function getTargetUserIds($course, $entry)
    {
        $courseTeachers = $this->courseService->getTeachersFromCourse($course);
        $entityUsers = $this->entityServiceManager->getEntityServiceByType($entry->getEntityType())->getUsersForEntity(
            $entry->getEntityId()
        );

        $targetUserIds = [];
        $targetUserIds[] = $entry->getUserId();

        foreach ($courseTeachers as $courseTeacher)
        {
            $targetUserIds[] = $courseTeacher->getId();
        }

        foreach ($entityUsers as $entityUser)
        {
            if($entityUser instanceof User)
            {
                $targetUserIds[] = $entityUser->getId();
            }
            else
            {
                $targetUserIds[] = $entityUser;
            }
        }

        return array_unique($targetUserIds);
    }

    /**
     * @param ContentObjectPublication $publication
     * @param Course $course
     * @param Assignment $assignment
     *
     * @return Filter[]
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function getFilters($publication, $course, $assignment): array
    {
        $filters = [
            $this->filterManager->getOrCreateFilterByPath(
                'Chamilo\\Application\\Weblcms::Course:' . $course->getId(),
                new TranslationContext(
                    'Chamilo\Application\Weblcms\Tool\Implementation\Assignment', 'NotificationFilterCourse',
                    ['{COURSE_TITLE}' => $course->get_title()]
                )
            ),
            $this->filterManager->getOrCreateFilterByPath(
                'Chamilo\\Application\\Weblcms::Tool:' . $publication->get_tool() . '::Course:' . $publication->get_course_id(),
                new TranslationContext(
                    'Chamilo\Application\Weblcms\Tool\Implementation\Assignment', 'NotificationFilterTool',
                    [
                        '{COURSE_TITLE}' => $course->get_title(),
                        '{TOOL}' => new TranslationContext(
                            'Chamilo\Application\Weblcms\Tool\\' . $publication->get_tool(), 'TypeName'
                        )
                    ]
                )
            ),
            $this->filterManager->getOrCreateFilterByPath(
                'Chamilo\\Application\\Weblcms::ContentObjectPublication:' . $publication->getId(),
                new TranslationContext(
                    'Chamilo\Application\Weblcms\Tool\Implementation\Assignment', 'NotificationFilterPublication',
                    [
                        '{COURSE_TITLE}' => $course->get_title(), '{PUBLICATION_TITLE}' => $assignment->get_title()
                    ]

                )
            )
        ];

        return $filters;
    }

    /**
     * @param Course $course
     * @param ContentObjectPublication $publication
     * @param Entry $entry
     *
     * @return string
     */
    protected function getNotificationUrl($course, $publication, $entry): string
    {
        $parameters = [
            Application::PARAM_CONTEXT => 'Chamilo\Application\Weblcms',
            Application::PARAM_ACTION => \Chamilo\Application\Weblcms\Manager::ACTION_VIEW_COURSE,
            \Chamilo\Application\Weblcms\Manager::PARAM_COURSE => $course->getId(),
            \Chamilo\Application\Weblcms\Manager::PARAM_TOOL => 'Assignment',
            \Chamilo\Application\Weblcms\Manager::PARAM_TOOL_ACTION => \Chamilo\Application\Weblcms\Tool\Implementation\Assignment\Manager::ACTION_DISPLAY,
            \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID => $publication->get_id(),
            \Chamilo\Application\Weblcms\Manager::PARAM_CATEGORY => $publication->get_category_id(),
            \Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager::PARAM_ACTION => \Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager::ACTION_ENTRY,
            \Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager::PARAM_ENTRY_ID => $entry->getId(),
            \Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager::PARAM_ENTITY_ID => $entry->getEntityId(),
            \Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager::PARAM_ENTITY_TYPE => $entry->getEntityType(
            )
        ];

        $redirect = new Redirect($parameters);
        $url = $redirect->getUrl();

        return $url;
    }

    /**
     * @param ContentObjectPublication $publication
     * @param Assignment $assignment
     * @param Course $course
     * @param Entry $entry
     *
     * @return ViewingContext[]
     */
    protected function getNotificationViewingContexts(
        ContentObjectPublication $publication, Assignment $assignment, Course $course, Entry $entry
    )
    {
        $viewingContexts = [];

        $viewingContexts[] = new ViewingContext(
            'Chamilo',
            new TranslationContext(
                'Chamilo\Application\Weblcms\Tool\Implementation\Assignment', 'NotificationNewAssignmentEntry',
                [
                    '{PUBLICATION_TITLE}' => $assignment->get_title(), '{COURSE_TITLE}' => $course->get_title(),
                    '{USER}' => $this->userService->getUserFullNameById($entry->getUserId())
                ]
            )
        );

        $viewingContexts[] = new ViewingContext(
            'Chamilo\\Application\\Weblcms::Course:' . $course->getId(),
            new TranslationContext(
                'Chamilo\Application\Weblcms\Tool\Implementation\Assignment', 'NotificationNewAssignmentEntryCourse',
                [
                    '{PUBLICATION_TITLE}' => $assignment->get_title(), '{COURSE_TITLE}' => $course->get_title(),
                    '{USER}' => $this->userService->getUserFullNameById($entry->getUserId())
                ]
            )
        );

        $viewingContexts[] = new ViewingContext(
            'Chamilo\\Application\\Weblcms::ContentObjectPublication:' . $publication->getId(),
            new TranslationContext(
                'Chamilo\Application\Weblcms\Tool\Implementation\Assignment', 'NotificationNewAssignmentEntryPublication',
                [
                    '{PUBLICATION_TITLE}' => $assignment->get_title(), '{COURSE_TITLE}' => $course->get_title(),
                    '{USER}' => $this->userService->getUserFullNameById($entry->getUserId())
                ]
            )
        );

        return $viewingContexts;
    }
}