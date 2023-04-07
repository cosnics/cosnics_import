<?php
namespace Chamilo\Core\Admin\Announcement\Service;

use Chamilo\Core\Admin\Announcement\Form\PublicationForm;
use Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication;
use Chamilo\Core\Admin\Announcement\Storage\Repository\PublicationRepository;
use Chamilo\Core\Group\Integration\Chamilo\Libraries\Rights\Service\GroupEntityProvider;
use Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\User\Integration\Chamilo\Libraries\Rights\Service\UserEntityProvider;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Translation\Translator;

class PublicationService
{
    /**
     * @var \Chamilo\Core\Group\Integration\Chamilo\Libraries\Rights\Service\GroupEntityProvider
     */
    private $groupEntityProvider;

    /**
     * @var \Chamilo\Core\Admin\Announcement\Storage\Repository\PublicationRepository
     */
    private $publicationRepository;

    /**
     * @var \Chamilo\Core\Admin\Announcement\Service\RightsService
     */
    private $rightsService;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    private $translator;

    /**
     * @var \Chamilo\Core\User\Integration\Chamilo\Libraries\Rights\Service\UserEntityProvider
     */
    private $userEntityProvider;

    /**
     * @param \Chamilo\Core\Admin\Announcement\Storage\Repository\PublicationRepository $publicationRepository
     * @param \Symfony\Component\Translation\Translator $translator
     * @param \Chamilo\Core\Admin\Announcement\Service\RightsService $rightsService
     * @param \Chamilo\Core\User\Integration\Chamilo\Libraries\Rights\Service\UserEntityProvider $userEntityProvider
     * @param \Chamilo\Core\Group\Integration\Chamilo\Libraries\Rights\Service\GroupEntityProvider $groupEntityProvider
     */
    public function __construct(
        PublicationRepository $publicationRepository, Translator $translator, RightsService $rightsService,
        UserEntityProvider $userEntityProvider, GroupEntityProvider $groupEntityProvider
    )
    {
        $this->publicationRepository = $publicationRepository;
        $this->translator = $translator;
        $this->rightsService = $rightsService;
        $this->userEntityProvider = $userEntityProvider;
        $this->groupEntityProvider = $groupEntityProvider;
    }

    /**
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return int
     */
    public function countPublications(Condition $condition = null)
    {
        return $this->getPublicationRepository()->countPublications($condition);
    }

    /**
     * @param $contentObjectIdentifier
     *
     * @return int
     */
    public function countPublicationsForContentObjectIdentifier(int $contentObjectIdentifier)
    {
        return $this->countPublicationsForContentObjectIdentifiers([$contentObjectIdentifier]);
    }

    /**
     * @param int $contentObjectIdentifiers
     *
     * @return int
     */
    public function countPublicationsForContentObjectIdentifiers(array $contentObjectIdentifiers)
    {
        return $this->getPublicationRepository()->countPublicationsForContentObjectIdentifiers(
            $contentObjectIdentifiers
        );
    }

    /**
     * @param int $type
     * @param int $objectIdentifier
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return int
     */
    public function countPublicationsForTypeAndIdentifier(int $type, int $objectIdentifier, Condition $condition = null)
    {
        if ($type !== PublicationAggregatorInterface::ATTRIBUTES_TYPE_OBJECT &&
            $type !== PublicationAggregatorInterface::ATTRIBUTES_TYPE_USER)
        {
            return 0;
        }
        else
        {
            return $this->getPublicationRepository()->countPublicationsForTypeAndIdentifier(
                $type, $objectIdentifier, $condition
            );
        }
    }

    /**
     * @param int $userIdentifier
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return int
     */
    public function countVisiblePublicationsForUserIdentifier(int $userIdentifier, Condition $condition = null)
    {
        $publicationIdentifiers = $this->getRightsService()->findPublicationIdentifiersWithViewRightForUserIdentifier(
            $userIdentifier
        );

        return $this->getPublicationRepository()->countVisiblePublicationsForPublicationIdentifiers(
            $condition, $publicationIdentifiers
        );
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication $publication
     *
     * @return bool
     */
    public function createPublication(Publication $publication)
    {
        $publication->set_publication_date(time());
        $publication->set_modification_date($publication->get_publication_date());

        if (!$this->getPublicationRepository()->createPublication($publication))
        {
            return false;
        }

        return $this->getRightsService()->createPublicationRightsLocation($publication);
    }

    /**
     * @param int $userIdentifier
     * @param int $contentObjectIdentifier
     * @param string[] $values
     *
     * @return \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication
     * @throws \Exception
     */
    public function createPublicationForUserIdentifierAndContentObjectIdentifierFromValues(
        int $userIdentifier, int $contentObjectIdentifier, array $values
    )
    {
        $publication = new Publication();
        $publication->set_content_object_id($contentObjectIdentifier);
        $publication->set_publisher_id($userIdentifier);

        if (!$this->savePublicationFromValues($publication, $userIdentifier, $values))
        {
            return false;
        }

        return $publication;
    }

    /**
     * @param int $userIdentifier
     * @param int $contentObjectIdentifiers
     * @param string[] $values
     *
     * @return bool
     * @throws \Exception
     */
    public function createPublicationsForUserIdentifierAndContentObjectIdentifiersFromValues(
        int $userIdentifier, array $contentObjectIdentifiers, array $values
    )
    {
        foreach ($contentObjectIdentifiers as $contentObjectIdentifier)
        {
            $publication = $this->createPublicationForUserIdentifierAndContentObjectIdentifierFromValues(
                $userIdentifier, $contentObjectIdentifier, $values
            );

            if (!$publication instanceof Publication)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication $publication
     *
     * @return bool
     */
    public function deletePublication(Publication $publication)
    {
        if (!$this->getRightsService()->deletePublicationRightsLocation($publication))
        {
            return false;
        }

        return $this->getPublicationRepository()->deletePublication($publication);
    }

    /**
     * @param int $publicationIdentifier
     *
     * @return bool
     */
    public function deletePublicationByIdentifier(int $publicationIdentifier)
    {
        $publication = $this->findPublicationByIdentifier($publicationIdentifier);

        if ($publication instanceof Publication)
        {
            return $this->deletePublication($publication);
        }
        else
        {
            return false;
        }
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     *
     * @return bool
     */
    public function deletePublicationsForContentObject(ContentObject $contentObject)
    {
        $publications = $this->findPublicationsForContentObjectIdentifier($contentObject->getId());

        foreach ($publications as $publication)
        {
            if (!$this->deletePublication($publication))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $publicationIdentifier
     *
     * @return \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication
     */
    public function findPublicationByIdentifier(int $publicationIdentifier)
    {
        return $this->getPublicationRepository()->findPublicationByIdentifier($publicationIdentifier);
    }

    /**
     * @param int $publicationIdentifier
     *
     * @return string[]
     * @throws \Exception
     */
    public function findPublicationRecordByIdentifier(int $publicationIdentifier)
    {
        return $this->getPublicationRepository()->findPublicationRecordByIdentifier($publicationIdentifier);
    }

    /**
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @param int $count
     * @param int $offset
     * @param \Chamilo\Libraries\Storage\Query\OrderBy $orderBy
     *
     * @return string[]
     * @throws \Exception
     */
    public function findPublicationRecords(
        Condition $condition = null, int $count = null, int $offset = null, ?OrderBy $orderBy = null
    )
    {
        return $this->getPublicationRepository()->findPublicationRecords($condition, $count, $offset, $orderBy);
    }

    /**
     * @param int $type
     * @param int $objectIdentifier
     * @param ?\Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @param ?int $count
     * @param ?int $offset
     * @param ?\Chamilo\Libraries\Storage\Query\OrderBy $orderProperties
     *
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication>
     */
    public function findPublicationRecordsForTypeAndIdentifier(
        int $type, int $objectIdentifier, ?Condition $condition = null, ?int $count = null, ?int $offset = null,
        ?OrderBy $orderProperties = null
    ): ArrayCollection
    {
        if ($type !== PublicationAggregatorInterface::ATTRIBUTES_TYPE_OBJECT &&
            $type !== PublicationAggregatorInterface::ATTRIBUTES_TYPE_USER)
        {
            return new ArrayCollection();
        }
        else
        {
            return $this->getPublicationRepository()->findPublicationRecordsForTypeAndIdentifier(
                $type, $objectIdentifier, $condition, $count, $offset, $orderProperties
            );
        }
    }

    /**
     * @param int $contentObjectIdentifier
     *
     * @return \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication[]
     */
    public function findPublicationsForContentObjectIdentifier(int $contentObjectIdentifier)
    {
        return $this->getPublicationRepository()->findPublicationsForContentObjectIdentifier($contentObjectIdentifier);
    }

    /**
     * @param int $userIdentifier
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @param int $count
     * @param int $offset
     * @param \Chamilo\Libraries\Storage\Query\OrderBy $orderBy
     *
     * @throws \Exception
     */
    public function findVisiblePublicationRecordsForUserIdentifier(
        int $userIdentifier, Condition $condition = null, int $count = null, int $offset = null,
        ?OrderBy $orderBy = null
    ): ArrayCollection
    {
        $publicationIdentifiers =
            $this->getRightsService()->findPublicationIdentifiersWithViewRightForUserIdentifier($userIdentifier);

        return $this->getPublicationRepository()->findVisiblePublicationRecordsForPublicationIdentifiers(
            $publicationIdentifiers, $condition, $count, $offset, $orderBy
        );
    }

    /**
     * @return \Chamilo\Core\Group\Integration\Chamilo\Libraries\Rights\Service\GroupEntityProvider
     */
    public function getGroupEntityProvider(): GroupEntityProvider
    {
        return $this->groupEntityProvider;
    }

    /**
     * @return \Chamilo\Core\Admin\Announcement\Storage\Repository\PublicationRepository
     */
    public function getPublicationRepository(): PublicationRepository
    {
        return $this->publicationRepository;
    }

    /**
     * @return \Chamilo\Core\Admin\Announcement\Service\RightsService
     */
    public function getRightsService(): RightsService
    {
        return $this->rightsService;
    }

    /**
     * @return \Symfony\Component\Translation\Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * @return \Chamilo\Core\User\Integration\Chamilo\Libraries\Rights\Service\UserEntityProvider
     */
    public function getUserEntityProvider(): UserEntityProvider
    {
        return $this->userEntityProvider;
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication $publication
     *
     * @return bool
     */
    public function savePublication(Publication $publication)
    {
        if ($publication->isIdentified())
        {
            return $this->updatePublication($publication);
        }
        else
        {
            return $this->createPublication($publication);
        }
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication $publication
     * @param int $userIdentifier
     * @param array $values
     *
     * @return bool
     * @throws \Exception
     */
    public function savePublicationFromValues(Publication $publication, int $userIdentifier, array $values)
    {
        if ($values[PublicationForm::PROPERTY_TIME_PERIOD_FOREVER] != 0)
        {
            $from = $to = 0;
        }
        else
        {
            $from = DatetimeUtilities::getInstance()->timeFromDatepicker($values[Publication::PROPERTY_FROM_DATE]);
            $to = DatetimeUtilities::getInstance()->timeFromDatepicker($values[Publication::PROPERTY_TO_DATE]);
        }

        $publication->set_from_date($from);
        $publication->set_to_date($to);
        $publication->set_hidden($values[Publication::PROPERTY_HIDDEN] ? 1 : 0);

        if (!$this->savePublication($publication))
        {
            return false;
        }

        return $this->getRightsService()->updatePublicationRights($publication, $userIdentifier, $values);
    }

    /**
     * @param \Chamilo\Core\Group\Integration\Chamilo\Libraries\Rights\Service\GroupEntityProvider $groupEntityProvider
     */
    public function setGroupEntityProvider(GroupEntityProvider $groupEntityProvider): void
    {
        $this->groupEntityProvider = $groupEntityProvider;
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Storage\Repository\PublicationRepository $publicationRepository
     */
    public function setPublicationRepository(PublicationRepository $publicationRepository): void
    {
        $this->publicationRepository = $publicationRepository;
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Service\RightsService $rightsService
     */
    public function setRightsService(RightsService $rightsService): void
    {
        $this->rightsService = $rightsService;
    }

    /**
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param \Chamilo\Core\User\Integration\Chamilo\Libraries\Rights\Service\UserEntityProvider $userEntityProvider
     */
    public function setUserEntityProvider(UserEntityProvider $userEntityProvider): void
    {
        $this->userEntityProvider = $userEntityProvider;
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Storage\DataClass\Publication $publication
     *
     * @return bool
     */
    public function updatePublication(Publication $publication)
    {
        $publication->set_modification_date(time());

        return $this->getPublicationRepository()->updatePublication($publication);
    }
}