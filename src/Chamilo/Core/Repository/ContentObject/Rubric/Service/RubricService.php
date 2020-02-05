<?php

namespace Chamilo\Core\Repository\ContentObject\Rubric\Service;

use Chamilo\Core\Repository\ContentObject\Rubric\Storage\Entity\RubricData;
use Chamilo\Core\Repository\ContentObject\Rubric\Storage\Repository\RubricDataRepository;

/**
 * Class RubricService
 *
 * @package Chamilo\Core\Repository\ContentObject\Rubric\Storage\Service
 *
 * @author - Sven Vanpoucke - Hogeschool Gent
 */
class RubricService
{
    /**
     * @var RubricDataRepository
     */
    protected $rubricDataRepository;

    /**
     * @var RubricValidator
     */
    protected $rubricValidator;

    /**
     * @var RubricTreeBuilder
     */
    protected $rubricTreeBuilder;

    /**
     * RubricService constructor.
     *
     * @param RubricDataRepository $rubricDataRepository
     * @param RubricValidator $rubricValidator
     * @param RubricTreeBuilder $rubricTreeBuilder
     */
    public function __construct(
        RubricDataRepository $rubricDataRepository, RubricValidator $rubricValidator,
        RubricTreeBuilder $rubricTreeBuilder
    )
    {
        $this->rubricDataRepository = $rubricDataRepository;
        $this->rubricValidator = $rubricValidator;
        $this->rubricTreeBuilder = $rubricTreeBuilder;
    }

    /**
     * Retrieves a rubric from the database
     *
     * @param int $rubricDataId
     * @param int $expectedVersion
     *
     * @return RubricData
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getRubric(int $rubricDataId, int $expectedVersion)
    {
        $rubric = $this->rubricTreeBuilder->buildRubricTreeByRubricDataId($rubricDataId, $expectedVersion);

        return $rubric;
    }

    /**
     * @param RubricData $rubricData
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Chamilo\Core\Repository\ContentObject\Rubric\Domain\Exceptions\InvalidTreeStructureException
     */
    public function saveRubric(RubricData $rubricData)
    {
        $rubricData->setLastUpdated(new \DateTime());
        $this->rubricValidator->validateRubric($rubricData);
        $this->rubricDataRepository->saveRubricData($rubricData);
    }
}
