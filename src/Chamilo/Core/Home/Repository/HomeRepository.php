<?php
namespace Chamilo\Core\Home\Repository;

use Chamilo\Configuration\Service\Consulter\RegistrationConsulter;
use Chamilo\Core\Home\Storage\DataClass\Block;
use Chamilo\Core\Home\Storage\DataClass\Element;
use Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\OrderProperty;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Doctrine\Common\Collections\ArrayCollection;

class HomeRepository
{
    protected DataClassRepository $dataClassRepository;

    protected RegistrationConsulter $registrationConsulter;

    public function __construct(DataClassRepository $dataClassRepository, RegistrationConsulter $registrationConsulter)
    {
        $this->dataClassRepository = $dataClassRepository;
        $this->registrationConsulter = $registrationConsulter;
    }

    public function countElementsByUserIdentifier(string $userIdentifier): int
    {
        $parameters = new DataClassCountParameters($this->getElementsByUserIdentifierCondition($userIdentifier));

        return $this->getDataClassRepository()->count(Element::class, $parameters);
    }

    /**
     * @throws \Chamilo\Libraries\Storage\Exception\DataClassNoResultException
     */
    public function createElement(Element $element): bool
    {
        return $this->getDataClassRepository()->create($element);
    }

    /**
     * @param string $userIdentifier
     *
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Home\Storage\DataClass\Block>
     * @throws \Chamilo\Libraries\Storage\Exception\DataClassNoResultException
     */
    public function findBlocksByUserIdentifier(string $userIdentifier): ArrayCollection
    {
        $parameters = new DataClassRetrievesParameters(
            new EqualityCondition(
                new PropertyConditionVariable(Element::class, Element::PROPERTY_USER_ID),
                new StaticConditionVariable($userIdentifier)
            )
        );

        return $this->getDataClassRepository()->retrieves(Block::class, $parameters);
    }

    public function findElementByIdentifier(string $elementIdentifier): ?Element
    {
        return $this->getDataClassRepository()->retrieveById(Element::class, $elementIdentifier);
    }

    /**
     * @param string $type
     * @param string $userIdentifier
     * @param string $parentIdentifier
     *
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Home\Storage\DataClass\Element>
     * @throws \Chamilo\Libraries\Storage\Exception\DataClassNoResultException
     */
    public function findElementsByTypeUserIdentifierAndParentIdentifier(
        string $type, string $userIdentifier, string $parentIdentifier = '0'
    ): ArrayCollection
    {
        $conditions = [];

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Element::class, Element::PROPERTY_TYPE), new StaticConditionVariable($type)
        );

        $conditions[] = $this->getElementsByUserIdentifierCondition($userIdentifier);

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Element::class, Element::PROPERTY_PARENT_ID),
            new StaticConditionVariable($parentIdentifier)
        );

        $parameters = new DataClassRetrievesParameters(
            new AndCondition($conditions), null, null, new OrderBy([
                new OrderProperty(new PropertyConditionVariable(Element::class, Element::PROPERTY_TYPE)),
                new OrderProperty(new PropertyConditionVariable(Element::class, Element::PROPERTY_SORT))
            ])
        );

        return $this->getDataClassRepository()->retrieves(Element::class, $parameters);
    }

    /**
     * @param string $userIdentifier
     *
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Home\Storage\DataClass\Element>
     * @throws \Chamilo\Libraries\Storage\Exception\DataClassNoResultException
     */
    public function findElementsByUserIdentifier(string $userIdentifier): ArrayCollection
    {
        $parameters = new DataClassRetrievesParameters(
            $this->getElementsByUserIdentifierCondition($userIdentifier), null, null, new OrderBy([
                new OrderProperty(new PropertyConditionVariable(Element::class, Element::PROPERTY_TYPE)),
                new OrderProperty(new PropertyConditionVariable(Element::class, Element::PROPERTY_SORT))
            ])
        );

        return $this->getDataClassRepository()->retrieves(Element::class, $parameters);
    }

    protected function getDataClassRepository(): DataClassRepository
    {
        return $this->dataClassRepository;
    }

    public function getElementsByUserIdentifierCondition(string $userIdentifier): EqualityCondition
    {
        return new EqualityCondition(
            new PropertyConditionVariable(Element::class, Element::PROPERTY_USER_ID),
            new StaticConditionVariable($userIdentifier)
        );
    }

    public function getRegistrationConsulter(): RegistrationConsulter
    {
        return $this->registrationConsulter;
    }
}