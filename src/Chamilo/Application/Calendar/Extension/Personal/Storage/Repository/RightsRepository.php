<?php
namespace Chamilo\Application\Calendar\Extension\Personal\Storage\Repository;

use Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication;
use Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\PublicationGroup;
use Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\PublicationUser;
use Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Parameters\RetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\RetrieveProperties;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * @package Chamilo\Application\Calendar\Extension\Personal\Storage\Repository
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class RightsRepository
{

    /**
     *
     * @var \Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository
     */
    private $dataClassRepository;

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository $dataClassRepository
     */
    public function __construct(DataClassRepository $dataClassRepository)
    {
        $this->dataClassRepository = $dataClassRepository;
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return integer
     */
    public function countPublicationGroupsForPublication(Publication $publication)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(PublicationGroup::class, PublicationGroup::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );

        return $this->getDataClassRepository()->count(
            PublicationGroup::class, new DataClassCountParameters($condition)
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return integer
     */
    public function countPublicationUsersForPublication(Publication $publication)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(PublicationUser::class, PublicationUser::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );

        return $this->getDataClassRepository()->count(
            PublicationUser::class, new DataClassCountParameters($condition)
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\PublicationGroup $publicationGroup
     *
     * @return boolean
     */
    public function createPublicationGroup(PublicationGroup $publicationGroup)
    {
        return $this->getDataClassRepository()->create($publicationGroup);
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\PublicationUser $publicationUser
     *
     * @return boolean
     */
    public function createPublicationUser(PublicationUser $publicationUser)
    {
        return $this->getDataClassRepository()->create($publicationUser);
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return boolean
     */
    public function deletePublicationGroupsForPublication(Publication $publication)
    {
        return $this->getDataClassRepository()->deletes(
            PublicationGroup::class, new EqualityCondition(
                new PropertyConditionVariable(PublicationGroup::class, PublicationGroup::PROPERTY_PUBLICATION),
                new StaticConditionVariable($publication->getId())
            )
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     * @param integer[] $groupIdentifiers
     *
     * @return boolean
     */
    public function deletePublicationGroupsForPublicationAndGroupIdentifiers(
        Publication $publication, array $groupIdentifiers
    )
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(PublicationGroup::class, PublicationGroup::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );
        $conditions[] = new InCondition(
            new PropertyConditionVariable(PublicationGroup::class, PublicationGroup::PROPERTY_GROUP_ID),
            $groupIdentifiers
        );

        return $this->getDataClassRepository()->deletes(
            PublicationGroup::class, new AndCondition($conditions)
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return boolean
     */
    public function deletePublicationUsersForPublication(Publication $publication)
    {
        return $this->getDataClassRepository()->deletes(
            PublicationUser::class, new EqualityCondition(
                new PropertyConditionVariable(PublicationUser::class, PublicationUser::PROPERTY_PUBLICATION),
                new StaticConditionVariable($publication->getId())
            )
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     * @param integer[] $userIdentifiers
     *
     * @return boolean
     */
    public function deletePublicationUsersForPublicationAndUserIdentifiers(
        Publication $publication, array $userIdentifiers
    )
    {
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(PublicationUser::class, PublicationUser::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );
        $conditions[] = new InCondition(
            new PropertyConditionVariable(PublicationUser::class, PublicationUser::PROPERTY_USER), $userIdentifiers
        );

        return $this->getDataClassRepository()->deletes(
            PublicationUser::class, new AndCondition($conditions)
        );
    }

    /**
     * @return \Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository
     */
    public function getDataClassRepository(): DataClassRepository
    {
        return $this->dataClassRepository;
    }

    /**
     * @param \Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository $dataClassRepository
     */
    public function setDataClassRepository(DataClassRepository $dataClassRepository): void
    {
        $this->dataClassRepository = $dataClassRepository;
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return integer[]
     */
    public function getPublicationGroupIdentifiersForPublication(Publication $publication)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(PublicationGroup::class, PublicationGroup::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );

        $properties = new RetrieveProperties(
            array(new PropertyConditionVariable(PublicationGroup::class, PublicationGroup::PROPERTY_GROUP_ID))
        );

        return $this->getDataClassRepository()->distinct(
            PublicationGroup::class, new DataClassDistinctParameters($condition, $properties)
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\PublicationGroup[]
     */
    public function getPublicationGroupsForPublication(Publication $publication)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(PublicationGroup::class, PublicationGroup::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );

        return $this->getDataClassRepository()->retrieves(
            PublicationGroup::class, new RetrievesParameters($condition)
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return integer[]
     */
    public function getPublicationUserIdentifiersForPublication(Publication $publication)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(PublicationUser::class, PublicationUser::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );

        $properties = new RetrieveProperties(
            array(new PropertyConditionVariable(PublicationUser::class, PublicationUser::PROPERTY_USER))
        );

        return $this->getDataClassRepository()->distinct(
            PublicationUser::class, new DataClassDistinctParameters($condition, $properties)
        );
    }

    /**
     * @param \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\Publication $publication
     *
     * @return \Chamilo\Application\Calendar\Extension\Personal\Storage\DataClass\PublicationUser[]
     */
    public function getPublicationUsersForPublication(Publication $publication)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(PublicationUser::class, PublicationUser::PROPERTY_PUBLICATION),
            new StaticConditionVariable($publication->getId())
        );

        return $this->getDataClassRepository()->retrieves(
            PublicationUser::class, new RetrievesParameters($condition)
        );
    }
}

