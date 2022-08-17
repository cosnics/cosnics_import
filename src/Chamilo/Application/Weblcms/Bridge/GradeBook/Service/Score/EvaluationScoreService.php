<?php
namespace Chamilo\Application\Weblcms\Bridge\GradeBook\Service\Score;

use Chamilo\Application\Weblcms\Bridge\Evaluation\Service\Entity\PublicationEntityServiceManager;
use Chamilo\Application\Weblcms\Bridge\GradeBook\Service\EntityDataService;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Tool\Implementation\Evaluation\Storage\Repository\PublicationRepository as EvaluationPublicationRepository;
use Chamilo\Core\Repository\ContentObject\Evaluation\Integration\Chamilo\Core\Repository\ContentObject\LearningPath\Domain\EvaluationConfiguration;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\TreeNode;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\LearningPathStepContextService;
use Chamilo\Libraries\Architecture\ContextIdentifier;
use Chamilo\Application\Weblcms\Tool\Implementation\Evaluation\Storage\DataClass\Publication as EvaluationPublication;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Service\Entity\EvaluationEntityRetrieveProperties;
use Chamilo\Libraries\Storage\FilterParameters\FilterParameters;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Service\Entity\EvaluationEntityServiceManager;
use Chamilo\Libraries\Storage\Iterator\RecordIterator;

/**
 * @package Chamilo\Application\Weblcms\Bridge\GradeBook\Service\Score
 *
 * @author Stefan Gabriëls - Hogeschool Gent
 */
class EvaluationScoreService implements ScoreServiceInterface, LearningPathScoreServiceInterface
{
    const AUTH_ABSENT = 'gafw';

    /**
     * @var EvaluationPublicationRepository
     */
    protected $evaluationPublicationRepository;

    /**
     * @var PublicationEntityServiceManager
     */
    protected $publicationEntityServiceManager;

    /**
     * @var EvaluationEntityServiceManager
     */
    protected $evaluationEntityServiceManager;

    /**
     * @var LearningPathStepContextService
     */
    protected $learningPathStepContextService;

    /**
     * @var EntityDataService
     */
    protected $entityDataService;

    /**
     * @param EvaluationPublicationRepository $evaluationPublicationRepository
     * @param PublicationEntityServiceManager $publicationEntityServiceManager
     * @param EvaluationEntityServiceManager $evaluationEntityServiceManager
     * @param LearningPathStepContextService $learningPathStepContextService
     * @param EntityDataService $entityDataService
     */
    public function __construct(EvaluationPublicationRepository $evaluationPublicationRepository, PublicationEntityServiceManager $publicationEntityServiceManager, EvaluationEntityServiceManager $evaluationEntityServiceManager, LearningPathStepContextService $learningPathStepContextService, EntityDataService $entityDataService)
    {
        $this->evaluationPublicationRepository = $evaluationPublicationRepository;
        $this->publicationEntityServiceManager = $publicationEntityServiceManager;
        $this->evaluationEntityServiceManager = $evaluationEntityServiceManager;
        $this->learningPathStepContextService = $learningPathStepContextService;
        $this->entityDataService = $entityDataService;
    }

    /**
     * @param ContentObjectPublication $publication
     *
     * @return array
     */
    public function getScores(ContentObjectPublication $publication): array
    {
        $entityType = $this->getEntityTypeFromPublication($publication);
        $contextIdentifier = new ContextIdentifier(EvaluationPublication::class_name(), $publication->getId());
        return $this->getUserScores($publication, $contextIdentifier, $entityType);
    }

    /**
     * @param ContentObjectPublication $publication
     * @param TreeNode $treeNode
     *
     * @return array
     */
    public function getScoresFromTreeNode(ContentObjectPublication $publication, TreeNode $treeNode): array
    {
        $entityType = $this->getEntityTypeFromTreeNode($treeNode);
        $contextIdentifier = $this->getContextIdentifierFromTreeNode($publication, $treeNode->getId());
        return $this->getUserScores($publication, $contextIdentifier, $entityType);
    }

    /**
     * @param ContentObjectPublication $publication
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     *
     * @return array
     */
    protected function getUserScores(ContentObjectPublication $publication, ContextIdentifier $contextIdentifier, int $entityType): array
    {
        $selectedEntities = $this->getSelectedEntitiesForPublication($publication, $contextIdentifier, $entityType);
        $scores = array();

        switch ($entityType)
        {
            case 0:
                foreach ($selectedEntities as $entity)
                {
                    $entityId = $entity['id'];
                    $score = $this->getEntityScore($entity);
                    $scores[$entityId] = $score;
                }
                return $scores;
            case 1:
                $userEntities = $this->entityDataService->getCourseGroupUserEntitiesRecursiveFromCourse($publication->get_course_id());
                break;
            case 2:
                $userEntities = $this->getPlatformGroupUserEntitiesFromEntityScores($selectedEntities);
                break;
        }

        foreach ($selectedEntities as $entity)
        {
            $entityId = $entity['id'];
            $score = $this->getEntityScore($entity);
            $users = $userEntities[$entityId];

            foreach ($users as $userId)
            {
                $hasKey = array_key_exists($userId, $scores);
                $curScore = $scores[$userId];
                if (!$hasKey || ($score == self::AUTH_ABSENT && is_null($curScore)) || (is_numeric($score) && (is_null($curScore) || $curScore == self::AUTH_ABSENT || (is_numeric($curScore) && $score > $curScore))))
                {
                    $scores[$userId] = $score;
                }
            }
        }
        return $scores;
    }

    /**
     * @param ContentObjectPublication $publication
     * @param ContextIdentifier $contextIdentifier
     * @param int $entityType
     *
     * @return RecordIterator
     */
    protected function getSelectedEntitiesForPublication(ContentObjectPublication $publication, ContextIdentifier $contextIdentifier, int $entityType): RecordIterator
    {
        $this->publicationEntityServiceManager->setContentObjectPublication($publication);
        $publicationEntityService = $this->publicationEntityServiceManager->getEntityServiceByType($entityType);
        $entityIds = $publicationEntityService->getTargetEntityIds();
        $entityService = $this->evaluationEntityServiceManager->getEntityServiceByType($entityType);
        return $entityService->getEntitiesFromIds($entityIds, $contextIdentifier, EvaluationEntityRetrieveProperties::ALL(), new FilterParameters());
    }

    /**
     * @param RecordIterator $entityScores
     *
     * @return array
     */
    protected function getPlatformGroupUserEntitiesFromEntityScores(RecordIterator $entityScores): array
    {
        $userEntities = [];
        foreach ($entityScores as $entityScore)
        {
            $entityId = $entityScore['id'];
            $userEntities[$entityId] = $this->entityDataService->getUserEntitiesFromPlatformGroup($entityId);
        }
        return $userEntities;
    }

    /**
     * @param ContentObjectPublication $publication
     *
     * @return int
     */
    protected function getEntityTypeFromPublication(ContentObjectPublication $publication): int
    {
        $evaluationPublication = $this->evaluationPublicationRepository->findPublicationByContentObjectPublication($publication);
        return $evaluationPublication->getEntityType();
    }

    /**
     * @param TreeNode $treeNode
     *
     * @return int
     */
    protected function getEntityTypeFromTreeNode(TreeNode $treeNode)
    {
        /** @var EvaluationConfiguration */
        $configuration = $treeNode->getConfiguration(new EvaluationConfiguration());
        return $configuration->getEntityType();
    }

    /**
     * @param ContentObjectPublication $publication
     * @param int $stepId
     *
     * @return ContextIdentifier
     */
    protected function getContextIdentifierFromTreeNode(ContentObjectPublication $publication, int $stepId): ContextIdentifier
    {
        $publicationClass = ContentObjectPublication::class_name();
        $publicationId = $publication->getId();
        $learningPathStepContext = $this->learningPathStepContextService->getOrCreateLearningPathStepContext($stepId, $publicationClass, $publicationId);
        return new ContextIdentifier(get_class($learningPathStepContext), $learningPathStepContext->getId());
    }

    /**
     * @param array $entity
     *
     * @return float|string|null
     */
    protected function getEntityScore(array $entity)
    {
        if ($entity['is_absent'])
        {
            return self::AUTH_ABSENT;
        }

        $score = $entity['score'];
        if (!is_null($score))
        {
            return (float) $score;
        }

        return null;
    }
}