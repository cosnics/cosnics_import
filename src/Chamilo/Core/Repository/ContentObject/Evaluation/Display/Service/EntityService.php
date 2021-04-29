<?php

namespace Chamilo\Core\Repository\ContentObject\Evaluation\Display\Service;

use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Storage\Repository\EntityRepository;
use Chamilo\Core\Repository\ContentObject\Evaluation\Storage\DataClass\EvaluationEntry;
use Chamilo\Core\Repository\ContentObject\Evaluation\Storage\DataClass\EvaluationEntryScore;
use Chamilo\Core\Repository\ContentObject\Evaluation\Storage\DataClass\EvaluationEntryScoreTargetUser;
use Chamilo\Core\User\Service\UserService;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\ContextIdentifier;
use Chamilo\Libraries\Storage\DataClass\CompositeDataClass;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\FilterParameters\FilterParameters;
use Chamilo\Libraries\Storage\Iterator\RecordIterator;

/**
 * @package Chamilo\Core\Repository\ContentObject\Evaluation\Display\Service
 *
 * @author Stefan Gabriëls - Hogeschool Gent
 */
class EntityService
{
    /**
     * @var EntityRepository
     */
    protected $entityRepository;

    /**
     * @var UserService
     */
    protected $userService;

    public function __construct(EntityRepository $entityRepository, UserService $userService)
    {
        $this->entityRepository = $entityRepository;
        $this->userService = $userService;
    }

    /**
     *
     * @param int[] $userIds
     * @param ContextIdentifier $contextIdentifier
     * @param FilterParameters|null $filterParameters
     *
     * @return RecordIterator
     */
    public function getUsersFromIDs(array $userIds, ContextIdentifier $contextIdentifier, FilterParameters $filterParameters = null): RecordIterator
    {
        if (is_null($filterParameters))
        {
            $filterParameters = new FilterParameters();
        }

        return $this->entityRepository->getUsersFromIDs($userIds, $contextIdentifier, $filterParameters);
    }

    /*public function getUserEntity(ContextIdentifier $contextIdentifier, int $entityType, int $entityId)
    {
        return $this->entityRepository->getUserEntity($contextIdentifier->getContextClass(), $contextIdentifier->getContextId(), $entityType, $entityId);
    }*/

    /**
     *
     * @param int[] $userIds
     * @param FilterParameters $filterParameters
     *
     * @return integer
     */
    public function countUsersFromIDs(array $userIds, FilterParameters $filterParameters): int
    {
        return $this->entityRepository->countUsersFromIDs($userIds, $filterParameters);
    }

    /**
     * @param int $entityId
     *
     * @return User
     */
    public function getUserForEntity(int $entityId): User
    {
        return $this->userService->findUserByIdentifier($entityId);
    }

    /**
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     * @param int $entityId
     * @return CompositeDataClass|DataClass|false
     */
    public function getEvaluationEntryForEntity(ContextIdentifier $contextIdentifier, int $entityType, int $entityId)
    {
        return $this->entityRepository->getEvaluationEntry($contextIdentifier, $entityType, $entityId);
    }

    /**
     * @param int $evaluationId
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     * @param int $entityId
     *
     * @return EvaluationEntry
     */
    private function createEvaluationEntry(int $evaluationId, ContextIdentifier $contextIdentifier, int $entityType, int $entityId): EvaluationEntry
    {
        $evaluationEntry = new EvaluationEntry();
        $evaluationEntry->setEvaluationId($evaluationId);
        $evaluationEntry->setContextClass($contextIdentifier->getContextClass());
        $evaluationEntry->setContextId($contextIdentifier->getContextId());
        $evaluationEntry->setEntityType($entityType);
        $evaluationEntry->setEntityId($entityId);
        $this->entityRepository->createEvaluationEntry($evaluationEntry);

        return $evaluationEntry;
    }

    /**
     * @param int $evaluationId
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     * @param int $entityId
     *
     * @return EvaluationEntry|CompositeDataClass|DataClass|false
     */
    public function createEvaluationEntryIfNotExists(int $evaluationId, ContextIdentifier $contextIdentifier, int $entityType, int $entityId)
    {
        return $this->entityRepository->getEvaluationEntry($contextIdentifier, $entityType, $entityId) ?:
            $this->createEvaluationEntry($evaluationId, $contextIdentifier, $entityType, $entityId);
    }

    /**
     * @param int $entryId
     * @return CompositeDataClass|DataClass|false
     */
    public function getEvaluationEntryScore(int $entryId)
    {
        return $this->entityRepository->getEvaluationEntryScore($entryId);
    }

    /**
     * @param int $entryId
     * @param int $evaluatorId
     * @param int $score
     * @param bool $isAbsent
     *
     * @return EvaluationEntryScore
     */
    private function createEvaluationEntryScore(int $entryId, int $evaluatorId, int $score = 0, bool $isAbsent = false): EvaluationEntryScore
    {
        $evaluationEntryScore = new EvaluationEntryScore();
        $evaluationEntryScore->setEvaluatorId($evaluatorId);
        $evaluationEntryScore->setEntryId($entryId);
        $evaluationEntryScore->setScore($score);
        $evaluationEntryScore->setIsAbsent($isAbsent);
        $evaluationEntryScore->setCreatedTime(time());
        $this->entityRepository->createEvaluationEntryScore($evaluationEntryScore);

        return $evaluationEntryScore;
    }

    /**
     * @param int $evaluationId
     * @param int $evaluatorId
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     * @param int $entityId
     * @param string $score
     *
     * @return EvaluationEntryScore
     */
    public function createOrUpdateEvaluationEntryScoreForEntity(int $evaluationId, int $evaluatorId, ContextIdentifier $contextIdentifier, int $entityType, int $entityId, int $score): EvaluationEntryScore
    {
        $evaluationEntry = $this->createEvaluationEntryIfNotExists($evaluationId, $contextIdentifier, $entityType, $entityId);

        $evaluationEntryScore = $this->entityRepository->getEvaluationEntryScore($evaluationEntry->getId());

        if ($evaluationEntryScore instanceof EvaluationEntryScore)
        {
            $evaluationEntryScore->setScore($score);
            $this->entityRepository->updateEvaluationEntryScore($evaluationEntryScore);
        } else
        {
            $evaluationEntryScore = $this->createEvaluationEntryScore($evaluationEntry->getId(), $evaluatorId, $score);
            $this->createEvaluationTargetUser($entityId, $evaluationEntryScore->getId());
        }

        return $evaluationEntryScore;
    }

    /**
     * @param int $evaluationId
     * @param int $evaluatorId
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     * @param int $entityId
     *
     * @return EvaluationEntryScore
     */
    public function saveEntityAsPresent(int $evaluationId, int $evaluatorId, ContextIdentifier $contextIdentifier, int $entityType, int $entityId): EvaluationEntryScore
    {
        $evaluationEntry = $this->createEvaluationEntryIfNotExists($evaluationId, $contextIdentifier, $entityType, $entityId);
        $evaluationEntryScore = $this->entityRepository->getEvaluationEntryScore($evaluationEntry->getId());

        if ($evaluationEntryScore instanceof EvaluationEntryScore)
        {
            $evaluationEntryScore->setIsAbsent(false);
            $this->entityRepository->updateEvaluationEntryScore($evaluationEntryScore);
        } else
        {
            $evaluationEntryScore = $this->createEvaluationEntryScore($evaluationEntry->getId(), $evaluatorId);
            $this->createEvaluationTargetUser($entityId, $evaluationEntryScore->getId());
        }

        return $evaluationEntryScore;
    }

    /**
     * @param int $evaluationId
     * @param int $evaluatorId
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     * @param int $entityId
     *
     * @return EvaluationEntryScore
     */
    public function saveEntityAsAbsent(int $evaluationId, int $evaluatorId, ContextIdentifier $contextIdentifier, int $entityType, int $entityId): EvaluationEntryScore
    {
        $evaluationEntry = $this->createEvaluationEntryIfNotExists($evaluationId, $contextIdentifier, $entityType, $entityId);
        $evaluationEntryScore = $this->entityRepository->getEvaluationEntryScore($evaluationEntry->getId());

        if ($evaluationEntryScore instanceof EvaluationEntryScore)
        {
            $evaluationEntryScore->setScore(0);
            $evaluationEntryScore->setIsAbsent(true);
            $this->entityRepository->updateEvaluationEntryScore($evaluationEntryScore);
        } else
        {
            $evaluationEntryScore = $this->createEvaluationEntryScore($evaluationEntry->getId(), $evaluatorId, 0, true);
            $this->createEvaluationTargetUser($entityId, $evaluationEntryScore->getId());
        }

        return $evaluationEntryScore;
    }

    /**
     * @param int $entityId
     * @param int $entryScoreId
     *
     * @return EvaluationEntryScoreTargetUser
     */
    private function createEvaluationTargetUser(int $entityId, int $entryScoreId): EvaluationEntryScoreTargetUser
    {
        $targetUser = new EvaluationEntryScoreTargetUser();
        $targetUser->setTargetUserId($entityId);
        $targetUser->setScoreId($entryScoreId);
        $this->entityRepository->createEvaluationEntryScoreTargetUser($targetUser);

        return $targetUser;
    }
}