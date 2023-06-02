<?php
namespace Chamilo\Core\Repository\Service;

use Chamilo\Core\Metadata\Entity\DataClassEntityFactory;
use Chamilo\Core\Metadata\Service\EntityService;
use Chamilo\Core\Metadata\Service\InstanceService;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataClass\ContentObjectAttachment;
use Chamilo\Core\Repository\Storage\DataClass\RepositoryCategory;
use Chamilo\Core\Repository\Storage\Repository\ContentObjectRepository;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\WorkspaceContentObjectRelation;
use Chamilo\Core\User\Manager;
use Chamilo\Core\User\Service\UserService;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\AttachmentSupport;
use Chamilo\Libraries\Utilities\StringUtilities;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @package Chamilo\Core\Repository\Service
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class ContentObjectSaver
{
    /**
     * @var \Chamilo\Libraries\Architecture\ClassnameUtilities
     */
    private $classnameUtilities;

    /**
     * @var \Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService
     */
    private $contentObjectRelationService;

    /**
     * @var \Chamilo\Core\Repository\Storage\Repository\ContentObjectRepository
     */
    private $contentObjectRepository;

    /**
     * @var \Chamilo\Core\Metadata\Entity\DataClassEntityFactory
     */
    private $dataClassEntityFactory;

    /**
     * @var \Chamilo\Core\Repository\Service\IncludeParserManager
     */
    private $includeParserManager;

    /**
     * @var \Chamilo\Core\Metadata\Service\EntityService
     */
    private $metadataEntityService;

    /**
     * @var \Chamilo\Core\Metadata\Service\InstanceService
     */
    private $metadataInstanceService;

    /**
     * @var \Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface
     */
    private $publicationAggregator;

    /**
     * @var \Chamilo\Core\Repository\Service\RepositoryCategoryService
     */
    private $repositoryCategoryService;

    private SessionInterface $session;

    /**
     * @var \Chamilo\Libraries\Utilities\StringUtilities
     */
    private $stringUtilities;

    /**
     * @var \Chamilo\Core\Repository\Service\TemplateRegistrationConsulter
     */
    private $templateRegistrationConsulter;

    /**
     * @var \Chamilo\Core\User\Service\UserService
     */
    private $userService;

    public function __construct(
        ContentObjectRepository $contentObjectRepository, RepositoryCategoryService $repositoryCategoryService,
        PublicationAggregatorInterface $publicationAggregator,
        ContentObjectRelationService $contentObjectRelationService, IncludeParserManager $includeParserManager,
        InstanceService $metadataInstanceService, DataClassEntityFactory $dataClassEntityFactory,
        EntityService $metadataEntityService, UserService $userService,
        TemplateRegistrationConsulter $templateRegistrationConsulter, SessionInterface $session,
        StringUtilities $stringUtilities, ClassnameUtilities $classnameUtilities
    )
    {
        $this->contentObjectRepository = $contentObjectRepository;
        $this->repositoryCategoryService = $repositoryCategoryService;
        $this->publicationAggregator = $publicationAggregator;
        $this->contentObjectRelationService = $contentObjectRelationService;
        $this->includeParserManager = $includeParserManager;
        $this->metadataInstanceService = $metadataInstanceService;
        $this->dataClassEntityFactory = $dataClassEntityFactory;
        $this->metadataEntityService = $metadataEntityService;
        $this->userService = $userService;
        $this->templateRegistrationConsulter = $templateRegistrationConsulter;
        $this->session = $session;
        $this->stringUtilities = $stringUtilities;
        $this->classnameUtilities = $classnameUtilities;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     *
     * @return bool
     */
    protected function allowsCategorySelection(ContentObject $contentObject)
    {
        return !$contentObject->isIdentified() || ($contentObject->isIdentified() &&
                $contentObject->get_owner_id() == $this->getSession()->get(Manager::SESSION_USER_IO));
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $attachmentIdentifier
     * @param string $type
     *
     * @return bool
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function attachContentObjectByIdentifierAndType(
        ContentObject $contentObject, int $attachmentIdentifier, string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        if ($this->isContentObjectAttachedTo($contentObject, $attachmentIdentifier, $type))
        {
            return true;
        }
        else
        {
            return $this->createContentObjectAttachment(
                $this->getContentObjectAttachmentInstanceFromParameters($contentObject, $attachmentIdentifier, $type)
            );
        }
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $attachmentIdentifiers
     * @param string $type
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function attachContentObjectsByIdentifierAndType(
        ContentObject $contentObject, array $attachmentIdentifiers = [], string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        foreach ($attachmentIdentifiers as $attachmentIdentifier)
        {
            if (!$this->attachContentObjectByIdentifierAndType($contentObject, $attachmentIdentifier, $type))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $attachmentIdentifier
     * @param string $type
     *
     * @return int
     * @throws \ReflectionException
     */
    public function countContentObjectAttachmentsByIdentifierAndType(
        ContentObject $contentObject, int $attachmentIdentifier = null, string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        return $this->getContentObjectRepository()->countContentObjectAttachmentsByIdentifierAndType(
            $contentObject, $attachmentIdentifier, $type
        );
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     *
     * @return bool
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function createContentObject(ContentObject $contentObject)
    {
        // version:
        // if the ID is set, we create a new version,
        // otherwise a new CO.

        $now = time();
        $contentObject->set_creation_date($now);
        $contentObject->set_modification_date($now);

        if (!$contentObject->get_template_registration_id())
        {
            $default_template_registration =
                $this->getTemplateRegistrationConsulter()->getTemplateRegistrationDefaultByType(
                    $contentObject::CONTEXT
                );

            $contentObject->set_template_registration_id($default_template_registration->getId());
        }

        if ($contentObject->isIdentified())
        { // id changes in create new version, so location needs to be fetched
            // now
            $contentObject->set_current(ContentObject::CURRENT_MULTIPLE);
        }
        else
        {
            $contentObject->set_object_number(Uuid::v4());
            $contentObject->set_current(ContentObject::CURRENT_SINGLE);
        }

        // TODO: The DataClass::checkBeforeSave() is currently ignored
        if (!$this->getContentObjectRepository()->createContentObject($contentObject))
        {
            return false;
        }

        if ($contentObject->isIdentified())
        {
            $contentObjectVersions = $this->findVersionsForContentObject($contentObject, true, false);

            foreach ($contentObjectVersions as $contentObjectVersion)
            {
                $contentObjectVersion->set_current(ContentObject::CURRENT_OLD);
                $this->updateContentObject($contentObjectVersion, false);
            }
        }

        return true;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObjectAttachment $contentObjectAttachment
     *
     * @return bool
     * @throws \Exception
     */
    public function createContentObjectAttachment(ContentObjectAttachment $contentObjectAttachment)
    {
        return $this->getContentObjectRepository()->createContentObjectAttachment($contentObjectAttachment);
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param array $values
     *
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObject
     * @throws \Chamilo\Libraries\Architecture\Exceptions\UserException
     * @throws \ReflectionException
     */
    public function createContentObjectFromInstanceAndValuesInWorkspace(
        Workspace $workspace, ContentObject $contentObject, array $values
    )
    {
        $contentObject->set_title($values[ContentObject::PROPERTY_TITLE]);
        $contentObject->set_description($values[ContentObject::PROPERTY_DESCRIPTION]);

        $this->createContentObject($contentObject);

        if ($contentObject->hasErrors())
        {
            return null;
        }

        if ($this->allowsCategorySelection($contentObject) && $workspace instanceof Workspace)
        {
            $this->setCategoryFromValuesInWorkspace($workspace, $contentObject, $values);
        }

        // Process includes
        $this->getIncludeParserManager()->parseContentObjectValues($contentObject, $values);

        // Process attachments
        if ($contentObject instanceof AttachmentSupport)
        {
            $this->attachContentObjectsByIdentifierAndType(
                $contentObject, $values[ContentObjectForm::PROPERTY_ATTACHMENTS]['content_object']
            );
        }

        return $contentObject;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObjectAttachment $contentObjectAttachment
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function deleteContentObjectAttachment(ContentObjectAttachment $contentObjectAttachment)
    {
        return $this->getContentObjectRepository()->deleteContentObjectAttachment($contentObjectAttachment);
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param string $type
     *
     * @return bool
     * @throws \Exception
     */
    public function detachAllContentObjectsByType(
        ContentObject $contentObject, string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        foreach ($this->findContentObjectAttachments($contentObject) as $contentObjectAttachment)
        {
            if (!$this->detachContentObjectByIdentifierAndType(
                $contentObject, $contentObjectAttachment->getId()
            ))
            {
                return false;
            }

            return true;
        }
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $attachmentIdentifier
     * @param string $type
     *
     * @return bool
     * @throws \Exception
     */
    public function detachContentObjectByIdentifierAndType(
        ContentObject $contentObject, int $attachmentIdentifier, string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        $attachment = $this->retrieveContentObjectAttachmentByIdentifierAndType(
            $contentObject, $attachmentIdentifier, $type
        );

        if ($attachment instanceof ContentObjectAttachment)
        {
            return $this->deleteContentObjectAttachment($attachment);
        }
        else
        {
            return false;
        }
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param string $type
     * @param \Chamilo\Libraries\Storage\Query\OrderBy $orderBy
     * @param int $offset
     * @param int $count
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @throws \Exception
     */
    public function findContentObjectAttachments(
        ContentObject $contentObject, $type = ContentObject::ATTACHMENT_NORMAL, $orderBy = null, $offset = null,
        $count = null
    )
    {
        return $this->getContentObjectRepository()->retrieveContentObjectAttachments(
            $contentObject, $type, $orderBy, $offset, $count
        );
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param bool $includeLast
     * @param bool $includeSelf
     *
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObject[]
     * @throws \ReflectionException
     */
    public function findVersionsForContentObject(
        ContentObject $contentObject, bool $includeLast = true, bool $includeSelf = true
    )
    {
        return $this->getContentObjectRepository()->retrieveVersionsForContentObject(
            $contentObject, $includeLast, $includeSelf
        );
    }

    /**
     * @param array $values
     *
     * @return int
     * @throws \Chamilo\Libraries\Architecture\Exceptions\UserException
     * @throws \ReflectionException
     */
    public function getCategoryIdentifierFromValuesInWorkspace(Workspace $workspace, array $values)
    {
        $parentIdentifier = (int) $values[ContentObject::PROPERTY_PARENT_ID];
        $newCategoryName = $values[ContentObjectForm::NEW_CATEGORY];

        if (!$this->getStringUtilities()->isNullOrEmpty($newCategoryName, true))
        {
            $newCategory = $this->getRepositoryCategoryService()->createNewCategoryInWorkspace(
                $workspace, $newCategoryName, $parentIdentifier
            );

            if ($newCategory instanceof RepositoryCategory)
            {
                $parentIdentifier = $newCategory->getId();
            }
        }

        return $parentIdentifier;
    }

    /**
     * @return \Chamilo\Libraries\Architecture\ClassnameUtilities
     */
    public function getClassnameUtilities(): ClassnameUtilities
    {
        return $this->classnameUtilities;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $attachmentIdentifier
     * @param string $type
     *
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObjectAttachment
     */
    public function getContentObjectAttachmentInstanceFromParameters(
        ContentObject $contentObject, int $attachmentIdentifier, string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        $attachment = new ContentObjectAttachment();

        $attachment->set_attachment_id($attachmentIdentifier);
        $attachment->set_content_object_id($contentObject->getId());
        $attachment->setType($type);

        return $attachment;
    }

    /**
     * @param int $templateIdentifier
     * @param int $userIdentfier
     *
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObject
     */
    public function getContentObjectInstanceForTemplateAndUserIdentfier(
        int $templateIdentifier, int $userIdentfier
    )
    {
        $contentObjectInstance = $this->getContentObjectInstanceForTemplateIdentfier($templateIdentifier);
        $contentObjectInstance->set_owner_id($userIdentfier);

        return $contentObjectInstance;
    }

    /**
     * @param int $templateIdentifier
     *
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObject
     */
    public function getContentObjectInstanceForTemplateIdentfier(int $templateIdentifier)
    {
        $templateRegistration =
            $this->getTemplateRegistrationConsulter()->getTemplateRegistrationByIdentifier($templateIdentifier);

        $contentObjectInstance = $templateRegistration->get_template()->get_content_object();
        $contentObjectInstance->set_template_registration_id($templateIdentifier);

        return $contentObjectInstance;
    }

    /**
     * @return \Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService
     */
    public function getContentObjectRelationService(): ContentObjectRelationService
    {
        return $this->contentObjectRelationService;
    }

    /**
     * @return \Chamilo\Core\Repository\Storage\Repository\ContentObjectRepository
     */
    public function getContentObjectRepository(): ContentObjectRepository
    {
        return $this->contentObjectRepository;
    }

    /**
     * @return \Chamilo\Core\Metadata\Entity\DataClassEntityFactory
     */
    public function getDataClassEntityFactory(): DataClassEntityFactory
    {
        return $this->dataClassEntityFactory;
    }

    /**
     * @return \Chamilo\Core\Repository\Service\IncludeParserManager
     */
    public function getIncludeParserManager(): IncludeParserManager
    {
        return $this->includeParserManager;
    }

    /**
     * @return \Chamilo\Core\Metadata\Service\EntityService
     */
    public function getMetadataEntityService(): EntityService
    {
        return $this->metadataEntityService;
    }

    /**
     * @return \Chamilo\Core\Metadata\Service\InstanceService
     */
    public function getMetadataInstanceService(): InstanceService
    {
        return $this->metadataInstanceService;
    }

    /**
     * @return \Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface
     */
    public function getPublicationAggregator(): PublicationAggregatorInterface
    {
        return $this->publicationAggregator;
    }

    /**
     * @return \Chamilo\Core\Repository\Service\RepositoryCategoryService
     */
    public function getRepositoryCategoryService(): RepositoryCategoryService
    {
        return $this->repositoryCategoryService;
    }

    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * @return \Chamilo\Libraries\Utilities\StringUtilities
     */
    public function getStringUtilities(): StringUtilities
    {
        return $this->stringUtilities;
    }

    /**
     * @return \Chamilo\Core\Repository\Service\TemplateRegistrationConsulter
     */
    public function getTemplateRegistrationConsulter(): TemplateRegistrationConsulter
    {
        return $this->templateRegistrationConsulter;
    }

    /**
     * @return \Chamilo\Core\User\Service\UserService
     */
    public function getUserService(): UserService
    {
        return $this->userService;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $attachmentIdentifier
     * @param string $type
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function isContentObjectAttachedTo(
        ContentObject $contentObject, int $attachmentIdentifier, string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        return $this->countContentObjectAttachmentsByIdentifierAndType($contentObject, $attachmentIdentifier, $type) >
            0;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param int $attachmentIdentifier
     * @param string $type
     *
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObjectAttachment
     * @throws \Exception
     */
    public function retrieveContentObjectAttachmentByIdentifierAndType(
        ContentObject $contentObject, int $attachmentIdentifier, string $type = ContentObject::ATTACHMENT_NORMAL
    )
    {
        return $this->getContentObjectRepository()->retrieveContentObjectAttachmentByIdentifierAndType(
            $contentObject, $attachmentIdentifier, $type
        );
    }

    /**
     * @param \Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace $workspace
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param array $values
     *
     * @throws \Chamilo\Libraries\Architecture\Exceptions\UserException
     * @throws \ReflectionException
     */
    public function setCategoryFromValuesInWorkspace(Workspace $workspace, ContentObject $contentObject, array $values)
    {
        $categoryIdentifier = $this->getCategoryIdentifierFromValuesInWorkspace($workspace, $values);

        $contentObjectRelationService = $this->getContentObjectRelationService();
        $contentObjectRelation = $contentObjectRelationService->getContentObjectRelationForWorkspaceAndContentObject(
            $workspace, $contentObject
        );

        if ($contentObjectRelation instanceof WorkspaceContentObjectRelation)
        {
            $contentObjectRelationService->updateContentObjectRelationFromParameters(
                $contentObjectRelation, $workspace->getId(), $contentObject->get_object_number(), $categoryIdentifier
            );
        }
        else
        {
            $contentObjectRelationService->createContentObjectRelationFromParameters(
                $workspace->getId(), $contentObject->get_object_number(), $categoryIdentifier
            );
        }
    }

    /**
     * @param \Chamilo\Libraries\Architecture\ClassnameUtilities $classnameUtilities
     */
    public function setClassnameUtilities(ClassnameUtilities $classnameUtilities): void
    {
        $this->classnameUtilities = $classnameUtilities;
    }

    /**
     * @param \Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService $contentObjectRelationService
     */
    public function setContentObjectRelationService(ContentObjectRelationService $contentObjectRelationService): void
    {
        $this->contentObjectRelationService = $contentObjectRelationService;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\Repository\ContentObjectRepository $contentObjectRepository
     */
    public function setContentObjectRepository(ContentObjectRepository $contentObjectRepository): void
    {
        $this->contentObjectRepository = $contentObjectRepository;
    }

    /**
     * @param \Chamilo\Core\Metadata\Entity\DataClassEntityFactory $dataClassEntityFactory
     */
    public function setDataClassEntityFactory(DataClassEntityFactory $dataClassEntityFactory): void
    {
        $this->dataClassEntityFactory = $dataClassEntityFactory;
    }

    /**
     * @param \Chamilo\Core\Repository\Service\IncludeParserManager $includeParserManager
     */
    public function setIncludeParserManager(IncludeParserManager $includeParserManager): void
    {
        $this->includeParserManager = $includeParserManager;
    }

    /**
     * @param \Chamilo\Core\Metadata\Service\EntityService $metadataEntityService
     */
    public function setMetadataEntityService(EntityService $metadataEntityService): void
    {
        $this->metadataEntityService = $metadataEntityService;
    }

    /**
     * @param \Chamilo\Core\Metadata\Service\InstanceService $metadataInstanceService
     */
    public function setMetadataInstanceService(InstanceService $metadataInstanceService): void
    {
        $this->metadataInstanceService = $metadataInstanceService;
    }

    /**
     * @param \Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface $publicationAggregator
     */
    public function setPublicationAggregator(PublicationAggregatorInterface $publicationAggregator): void
    {
        $this->publicationAggregator = $publicationAggregator;
    }

    /**
     * @param \Chamilo\Core\Repository\Service\RepositoryCategoryService $repositoryCategoryService
     */
    public function setRepositoryCategoryService(
        RepositoryCategoryService $repositoryCategoryService
    ): void
    {
        $this->repositoryCategoryService = $repositoryCategoryService;
    }

    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param \Chamilo\Libraries\Utilities\StringUtilities $stringUtilities
     */
    public function setStringUtilities(StringUtilities $stringUtilities): void
    {
        $this->stringUtilities = $stringUtilities;
    }

    /**
     * @param \Chamilo\Core\Repository\Service\TemplateRegistrationConsulter $templateRegistrationConsulter
     */
    public function setTemplateRegistrationConsulter(TemplateRegistrationConsulter $templateRegistrationConsulter): void
    {
        $this->templateRegistrationConsulter = $templateRegistrationConsulter;
    }

    /**
     * @param \Chamilo\Core\User\Service\UserService $userService
     */
    public function setUserService(UserService $userService): void
    {
        $this->userService = $userService;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param bool $trueUpdate
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function updateContentObject(ContentObject $contentObject, bool $trueUpdate = true)
    {
        $versions = $this->findVersionsForContentObject($contentObject);

        foreach ($versions as $version)
        {
            if (!$this->getPublicationAggregator()->canContentObjectBeEdited($version->getId()))
            {
                return false;
            }
        }

        if ($trueUpdate)
        {
            $contentObject->set_modification_date(time());
        }

        // TODO: The DataClass::checkBeforeSave() is currently ignored
        $success = $this->getContentObjectRepository()->updateContentObject($contentObject);

        if (!$success)
        {
            return false;
        }

        return true;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param array $values
     *
     * @return bool
     * @throws \Chamilo\Libraries\Architecture\Exceptions\UserException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function updateContentObjectFromInstanceAndValuesInWorkspace(
        Workspace $workspace, ContentObject $contentObject, array $values
    )
    {
        $contentObject->set_title($values[ContentObject::PROPERTY_TITLE]);
        $contentObject->set_description($values[ContentObject::PROPERTY_DESCRIPTION]);

        if (isset($values[ContentObjectForm::PROPERTY_VERSION]) && $values[ContentObjectForm::PROPERTY_VERSION] == 1)
        {
            $contentObject->set_comment(nl2br($values[ContentObject::PROPERTY_COMMENT]));

            $result = $this->createContentObject($contentObject);

            foreach ($this->findVersionsForContentObject($contentObject) as $contentObjectVersion)
            {
                if ($contentObjectVersion->get_parent_id() != $contentObject->get_parent_id())
                {
                    $contentObjectVersion->set_parent_id($contentObject->get_parent_id());
                    $this->updateContentObject($contentObjectVersion, false);
                }
            }
        }
        else
        {
            $result = $this->updateContentObject($contentObject);
        }

        if ($contentObject->hasErrors())
        {
            return false;
        }

        if ($this->allowsCategorySelection($contentObject) && $workspace instanceof Workspace)
        {
            $this->setCategoryFromValuesInWorkspace($workspace, $contentObject, $values);
        }

        // Process includes
        $this->getIncludeParserManager()->parseContentObjectValues($contentObject, $values);

        // Process attachments
        if ($contentObject instanceof AttachmentSupport)
        {
            /*
             * TODO: Make this faster by providing a function that matches the existing IDs against the ones that need
             * to be added, and attaches and detaches accordingly.
             */
            $this->detachAllContentObjectsByType($contentObject);
            $this->attachContentObjectsByIdentifierAndType(
                $contentObject, $values[ContentObjectForm::PROPERTY_ATTACHMENTS]['content_object']
            );
        }

        $user = $this->getUserService()->findUserByIdentifier($contentObject->get_owner_id());

        $this->getMetadataInstanceService()->updateInstances(
            $user, $contentObject, (array) $values[InstanceService::PROPERTY_METADATA_ADD_SCHEMA]
        );

        $this->getMetadataEntityService()->updateEntitySchemaValues(
            $user, $this->getDataClassEntityFactory()->getEntityFromDataClass($contentObject),
            $values[EntityService::PROPERTY_METADATA_SCHEMA]
        );

        return $result;
    }
}