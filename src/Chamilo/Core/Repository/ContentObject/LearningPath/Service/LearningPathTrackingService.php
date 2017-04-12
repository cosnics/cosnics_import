<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Service;

use Chamilo\Core\Repository\ContentObject\Assessment\Storage\DataClass\Assessment;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Attempt\LearningPathAttempt;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Attempt\LearningPathChildAttempt;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Attempt\LearningPathQuestionAttempt;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTree;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTreeNode;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\LearningPath;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\Repository\LearningPathTrackingRepositoryInterface;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;

/**
 * Service to manage the tracking of attempts in a learning path
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class LearningPathTrackingService
{
    /**
     * @var LearningPathAttemptService
     */
    protected $learningPathAttemptService;

    /**
     * @var LearningPathTrackingRepositoryInterface
     */
    protected $learningPathTrackingRepository;

    /**
     * LearningPathTrackingService constructor.
     *
     * @param LearningPathAttemptService $learningPathAttemptService
     * @param LearningPathTrackingRepositoryInterface $learningPathTrackingRepository
     */
    public function __construct(
        LearningPathAttemptService $learningPathAttemptService,
        LearningPathTrackingRepositoryInterface $learningPathTrackingRepository
    )
    {
        $this->learningPathAttemptService = $learningPathAttemptService;
        $this->learningPathTrackingRepository = $learningPathTrackingRepository;
    }

    /**
     * Tracks an attempt for a given user
     *
     * @param LearningPath $learningPath
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param User $user
     */
    public function trackAttemptForUser(
        LearningPath $learningPath, LearningPathTreeNode $learningPathTreeNode, User $user
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);

        $this->learningPathAttemptService->getOrCreateActiveLearningPathChildAttempt(
            $learningPathAttempt, $learningPathTreeNode
        );
    }

    /**
     * Change the status of a given learning path tree node
     *
     * @param LearningPath $learningPath
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param User $user
     * @param string $newStatus
     */
    public function changeActiveAttemptStatus(
        LearningPath $learningPath, LearningPathTreeNode $learningPathTreeNode, User $user,
        $newStatus = LearningPathChildAttempt::STATUS_COMPLETED
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
        $activeAttempt = $this->learningPathAttemptService->getOrCreateActiveLearningPathChildAttempt(
            $learningPathAttempt, $learningPathTreeNode
        );

        $activeAttempt->set_status($newStatus);
        $this->learningPathTrackingRepository->update($activeAttempt);

        if ($activeAttempt->isFinished())
        {
            $this->recalculateLearningPathProgress($learningPath, $user, $learningPathTreeNode->getLearningPathTree());
        }
    }

    /**
     * Returns the identifier for the active LearningPathChildAttempt
     *
     * @param LearningPath $learningPath
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param User $user
     *
     * @return int
     */
    public function getActiveAttemptId(
        LearningPath $learningPath, LearningPathTreeNode $learningPathTreeNode, User $user
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
        $activeAttempt = $this->learningPathAttemptService->getOrCreateActiveLearningPathChildAttempt(
            $learningPathAttempt, $learningPathTreeNode
        );

        return $activeAttempt->getId();
    }

    /**
     * Calculates and stores the total time for the active attempt of the given learning path three node for a given
     * user
     *
     * @param LearningPath $learningPath
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param User $user
     */
    public function setActiveAttemptTotalTime(
        LearningPath $learningPath, LearningPathTreeNode $learningPathTreeNode, User $user
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
        $activeAttempt = $this->learningPathAttemptService->getOrCreateActiveLearningPathChildAttempt(
            $learningPathAttempt, $learningPathTreeNode
        );

        $activeAttempt->calculateAndSetTotalTime();
        $this->learningPathTrackingRepository->update($activeAttempt);
    }

    /**
     * Sets the total time of a given attempt identified by the learning path child attempt id
     *
     * @param $learningPathChildAttemptId
     *
     * @throws ObjectNotExistException
     */
    public function setAttemptTotalTimeByLearningPathChildAttemptId($learningPathChildAttemptId)
    {
        $learningPathChildAttempt =
            $this->learningPathTrackingRepository->findLearningPathChildAttemptById($learningPathChildAttemptId);

        if (!$learningPathChildAttempt instanceof LearningPathChildAttempt)
        {
            throw new ObjectNotExistException('LearningPathAttempt');
        }

        $learningPathChildAttempt->calculateAndSetTotalTime();
        $this->learningPathTrackingRepository->update($learningPathChildAttempt);
    }

    /**
     * Recalculates and updates the progress of the learning path tree
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTree $learningPathTree
     */
    public function recalculateLearningPathProgress(
        LearningPath $learningPath, User $user, LearningPathTree $learningPathTree
    )
    {
        $nodesCompleted = 0;

        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);

        foreach ($learningPathTree->getLearningPathTreeNodes() as $learningPathTreeNode)
        {
            if ($this->isLearningPathTreeNodeCompleted($learningPath, $user, $learningPathTreeNode))
            {
                $nodesCompleted ++;
            }
        }

        $progress = round(($nodesCompleted / count($learningPathTree->getLearningPathTreeNodes())) * 100);
        $learningPathAttempt->set_progress($progress);

        $this->learningPathTrackingRepository->update($learningPathAttempt);
    }

    /**
     * Returns the progress for a given user in a given learning path
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return int
     */
    public function getLearningPathProgress(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode = null
    )
    {
        if (is_null($learningPathTreeNode) || $learningPathTreeNode->isRootNode())
        {
            $learningPathAttempt =
                $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);

            return $learningPathAttempt->get_progress();
        }

        $descendantNodes = $learningPathTreeNode->getDescendantNodes();
        if (empty($descendantNodes))
        {
            return 100;
        }

        $completedCount = 0;

        foreach ($descendantNodes as $descendantNode)
        {
            if ($this->isLearningPathTreeNodeCompleted($learningPath, $user, $descendantNode))
            {
                $completedCount ++;
            }
        }

        return (int) round(($completedCount / count($descendantNodes) * 100));
    }

    /**
     * Checks if a given learning path tree node is completed
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return bool
     */
    public function isLearningPathTreeNodeCompleted(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
        $learningPathChildAttempts =
            $this->learningPathAttemptService->getLearningPathChildAttempts($learningPathAttempt);

        if ($learningPathTreeNode->hasChildNodes())
        {
            $completed = true;

            foreach ($learningPathTreeNode->getChildNodes() as $childLearningPathTreeNode)
            {
                $completed &= $this->isLearningPathTreeNodeCompleted(
                    $learningPath, $user, $childLearningPathTreeNode
                );
            }

            return $completed;
        }

        /** @var LearningPathChildAttempt[] $learningPathTreeNodeAttempts */
        $learningPathTreeNodeAttempts = $learningPathChildAttempts[$learningPathTreeNode->getId()];

        foreach ($learningPathTreeNodeAttempts as $learningPathTreeNodeAttempt)
        {
            if ($learningPathTreeNodeAttempt->isFinished())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether or not the maximum number of attempts is reached for the given LearningPath, User
     * and LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return bool
     */
    public function isMaximumAttemptsReachedForAssessment(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);

        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
        $learningPathChildAttempts =
            $this->learningPathAttemptService->getLearningPathChildAttempts($learningPathAttempt);

        /** @var Assessment $assessment */
        $assessment = $learningPathTreeNode->getContentObject();

        return $assessment->get_maximum_attempts() > 0 &&
            count($learningPathChildAttempts) > $assessment->get_maximum_attempts();
    }

    /**
     * Saves the answer, score and hint for a question for the given LearningPath, User, LearningPathTreeNode and
     * Question identifier
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param int $questionIdentifier
     * @param string $answer
     * @param int $score
     * @param string $hint
     */
    public function saveAnswerForQuestion(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode, $questionIdentifier,
        $answer = '', $score = 0, $hint = ''
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);

        $learningPathQuestionAttempts = $this->getQuestionAttempts($learningPath, $user, $learningPathTreeNode);
        $learningPathQuestionAttempt = $learningPathQuestionAttempts[$questionIdentifier];

        if (!$learningPathQuestionAttempt instanceof LearningPathQuestionAttempt)
        {
            throw new \RuntimeException(
                sprintf('The given LearningPathQuestionAttempt for the question %s is not found', $questionIdentifier)
            );
        }

        $learningPathQuestionAttempt->set_answer($answer);
        $learningPathQuestionAttempt->set_score($score);
        $learningPathQuestionAttempt->set_hint($hint);

        $this->learningPathTrackingRepository->update($learningPathQuestionAttempt);
    }

    /**
     * Saves the assessment score for the given LearningPath, User and LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param int $assessmentScore
     */
    public function saveAssessmentScore(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode, $assessmentScore = 0
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);

        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
        $activeAttempt = $this->learningPathAttemptService->getOrCreateActiveLearningPathChildAttempt(
            $learningPathAttempt, $learningPathTreeNode
        );

        $activeAttempt->set_score($assessmentScore);
        $activeAttempt->calculateAndSetTotalTime();
        $activeAttempt->set_status($this->determineStatusForAssessmentByScore($learningPathTreeNode, $assessmentScore));

        $this->learningPathTrackingRepository->update($activeAttempt);
        $this->recalculateLearningPathProgress($learningPath, $user, $learningPathTreeNode->getLearningPathTree());
    }

    /**
     * Changes the assessment score for the given LearningPath, User, LearningPathTreeNode and
     * LearningPathChildAttemptId
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param null $learningPathChildAttemptId
     * @param int $newScore
     */
    public function changeAssessmentScore(
        LearningPath $learningPath, User $user,
        LearningPathTreeNode $learningPathTreeNode, $learningPathChildAttemptId, $newScore = 0
    )
    {
        $learningPathChildAttempt = $this->getLearningPathChildAttemptById(
            $learningPath, $user, $learningPathTreeNode, $learningPathChildAttemptId
        );

        $learningPathChildAttempt->set_score($newScore);

        $learningPathChildAttempt->set_status(
            $this->determineStatusForAssessmentByScore($learningPathTreeNode, $newScore)
        );

        $this->learningPathTrackingRepository->update($learningPathChildAttempt);
        $this->recalculateLearningPathProgress($learningPath, $user, $learningPathTreeNode->getLearningPathTree());
    }

    /**
     * Determines the status for a given assessment LearningPathTreeNode based on the given score
     *
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param int $assessmentScore
     *
     * @return string
     */
    protected function determineStatusForAssessmentByScore(
        LearningPathTreeNode $learningPathTreeNode, $assessmentScore = 0
    )
    {
        $masteryScore = $learningPathTreeNode->getLearningPathChild()->getMasteryScore();
        if ($masteryScore > 0)
        {
            $status = ($assessmentScore >= $masteryScore) ? LearningPathChildAttempt::STATUS_PASSED :
                LearningPathChildAttempt::STATUS_FAILED;
        }
        else
        {
            $status = LearningPathChildAttempt::STATUS_COMPLETED;
        }

        return $status;
    }

    /**
     * Returns the question attempts for a given LearningPath, User and LearningPathTreeNode
     * using the given attempt (by id) or the active attempt
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param int $learningPathChildAttemptId
     *
     * @return LearningPathQuestionAttempt[]
     */
    public function getQuestionAttempts(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode,
        $learningPathChildAttemptId = null
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);

        if (is_null($learningPathChildAttemptId))
        {
            $learningPathAttempt =
                $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
            $learningPathChildAttempt = $this->learningPathAttemptService->getOrCreateActiveLearningPathChildAttempt(
                $learningPathAttempt, $learningPathTreeNode
            );
        }
        else
        {
            $learningPathChildAttempt = $this->getLearningPathChildAttemptById(
                $learningPath, $user, $learningPathTreeNode, $learningPathChildAttemptId
            );
        }

        $questionAttempts = $this->learningPathAttemptService->getLearningPathQuestionAttempts(
            $learningPathChildAttempt
        );

        $questionAttemptPerQuestion = array();

        foreach ($questionAttempts as $questionAttempt)
        {
            $questionAttemptPerQuestion[$questionAttempt->get_question_complex_id()] = $questionAttempt;
        }

        return $questionAttemptPerQuestion;
    }

    /**
     * Registers the question attempts for the given question identifiers
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param int[] $questionIdentifiers
     *
     * @return LearningPathQuestionAttempt[]
     */
    public function registerQuestionAttempts(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode,
        $questionIdentifiers = array()
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);

        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);
        $activeAttempt = $this->learningPathAttemptService->getOrCreateActiveLearningPathChildAttempt(
            $learningPathAttempt, $learningPathTreeNode
        );

        $questionAttemptPerQuestion = array();
        foreach ($questionIdentifiers as $questionIdentifier)
        {
            $questionAttemptPerQuestion[$questionIdentifier] =
                $this->learningPathAttemptService->createLearningPathQuestionAttempt(
                    $activeAttempt, $questionIdentifier
                );
        }

        return $questionAttemptPerQuestion;
    }

    /**
     * Returns a LearningPathChildAttempt by a given id, validating that it belongs to the attempt of the given user
     * and learning path tree node
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param $learningPathChildAttemptId
     *
     * @return LearningPathChildAttempt
     */
    public function getLearningPathChildAttemptById(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode, $learningPathChildAttemptId
    )
    {
        $learningPathTreeNodeAttempts = $this->getLearningPathTreeNodeAttempts(
            $learningPath, $user, $learningPathTreeNode
        );

        foreach ($learningPathTreeNodeAttempts as $learningPathTreeNodeAttempt)
        {
            if ($learningPathTreeNodeAttempt->getId() == $learningPathChildAttemptId)
            {
                return $learningPathTreeNodeAttempt;
            }
        }

        throw new \RuntimeException('Could not find the LearningPathChildAttempt by id ' . $learningPathChildAttemptId);
    }

    /**
     * Deletes the learning path child attempt by a given id. Verifies that this identifier belongs to the attempts
     * for the given learning path and user
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     * @param int $learningPathChildAttemptId
     */
    public function deleteLearningPathChildAttemptById(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode, $learningPathChildAttemptId
    )
    {
        $learningPathTreeNodeAttempt = $this->getLearningPathChildAttemptById(
            $learningPath, $user, $learningPathTreeNode, $learningPathChildAttemptId
        );

        $this->learningPathAttemptService->deleteLearningPathChildAttempt($learningPathTreeNodeAttempt);
        $this->recalculateLearningPathProgress($learningPath, $user, $learningPathTreeNode->getLearningPathTree());
    }

    /**
     * Deletes the learning path child attempts for a given LearningPathTreeNode.
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     */
    public function deleteLearningPathChildAttemptsForLearningPathTreeNode(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $learningPathTreeNodeAttempts = $this->getLearningPathTreeNodeAttempts(
            $learningPath, $user, $learningPathTreeNode
        );

        foreach ($learningPathTreeNodeAttempts as $learningPathTreeNodeAttempt)
        {
            $this->learningPathAttemptService->deleteLearningPathChildAttempt($learningPathTreeNodeAttempt);
        }

        $this->recalculateLearningPathProgress($learningPath, $user, $learningPathTreeNode->getLearningPathTree());
    }

    /**
     * Deletes the learning path attempt for the given user
     *
     * @param LearningPath $learningPath
     * @param User $user
     */
    public function deleteLearningPathAttempt(LearningPath $learningPath, User $user)
    {
        $learningPathAttempt = $this->learningPathAttemptService->getLearningPathAttemptForUser($learningPath, $user);

        if ($learningPathAttempt instanceof LearningPathAttempt)
        {
            $this->learningPathAttemptService->deleteLearningPathAttempt($learningPathAttempt);
        }
    }

    /**
     * Validates that the given LearningPathTreeNode contains an assessment content object
     *
     * @param LearningPathTreeNode $learningPathTreeNode
     */
    protected function validateLearningPathTreeNodeIsAssessment(LearningPathTreeNode $learningPathTreeNode)
    {
        if (!$learningPathTreeNode->getContentObject() instanceof Assessment)
        {
            throw new \RuntimeException(
                'The given LearningPathTreeNode is not connected to an assessment'
            );
        }
    }

    /**
     * Returns whether or not the given LearningPathTreeNode is blocked for the given user
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return bool
     */
    public function isCurrentLearningPathTreeNodeBlocked(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);

        $learningPathChildAttempts =
            $this->learningPathAttemptService->getLearningPathChildAttempts($learningPathAttempt);

        $previousNodes = $learningPathTreeNode->getPreviousNodes();

        foreach ($previousNodes as $previousNode)
        {
            if (
                $learningPath->enforcesDefaultTraversingOrder() ||
                (!$previousNode->isRootNode() && $previousNode->getLearningPathChild()->isBlocked())
            )
            {
                if (count($learningPathChildAttempts[$previousNode->getId()]) == 0)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns a list of the nodes that are responsible that a step can not be taken
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return LearningPathTreeNode[]
     */
    public function getResponsibleNodesForBlockedLearningPathTreeNode(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);

        $learningPathChildAttempts =
            $this->learningPathAttemptService->getLearningPathChildAttempts($learningPathAttempt);

        $previousNodes = $learningPathTreeNode->getPreviousNodes();

        $blockedNodes = array();

        foreach ($previousNodes as $previousNode)
        {
            if (
                $learningPath->enforcesDefaultTraversingOrder() ||
                (!$previousNode->isRootNode() && $previousNode->getLearningPathChild()->isBlocked())
            )
            {
                if (count($learningPathChildAttempts[$previousNode->getId()]) == 0)
                {
                    $blockedNodes[] = $previousNode;
                }
            }
        }

        return $blockedNodes;
    }

    /**
     * Returns whether or not the LearningPathTreeNode has attempts
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return bool
     */
    public function hasLearningPathTreeNodeAttempts(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        return $this->countLearningPathTreeNodeAttempts($learningPath, $user, $learningPathTreeNode) > 0;
    }

    /**
     * Returns the number of attempts for a given LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return int
     */
    public function countLearningPathTreeNodeAttempts(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        return count($this->getLearningPathTreeNodeAttempts($learningPath, $user, $learningPathTreeNode));
    }

    /**
     * Returns the attempts for a given LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return LearningPathChildAttempt[]
     */
    public function getLearningPathTreeNodeAttempts(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $learningPathAttempt =
            $this->learningPathAttemptService->getOrCreateLearningPathAttemptForUser($learningPath, $user);

        return $this->learningPathAttemptService->getLearningPathChildAttemptsForLearningPathTreeNode(
            $learningPathAttempt, $learningPathTreeNode
        );
    }

    /**
     * Returns the total time spent in the given LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return int|string
     */
    public function getTotalTimeSpentInLearningPathTreeNode(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $totalTime = 0;

        $learningPathAttempts = $this->getLearningPathTreeNodeAttempts($learningPath, $user, $learningPathTreeNode);
        foreach ($learningPathAttempts as $learningPathAttempt)
        {
            $totalTime += $learningPathAttempt->get_total_time();
        }

        if ($learningPathTreeNode->hasChildNodes())
        {
            foreach ($learningPathTreeNode->getChildNodes() as $childNode)
            {
                $totalTime += $this->getTotalTimeSpentInLearningPathTreeNode($learningPath, $user, $childNode);
            }
        }

        return $totalTime;
    }

    /**
     * Returns the average score of the given user  in the given LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return float
     */
    public function getAverageScoreInLearningPathTreeNode(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        try
        {
            $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);
        }
        catch (\Exception $ex)
        {
            return null;
        }

        $totalScore = 0;

        $learningPathAttempts = $this->getLearningPathTreeNodeAttempts($learningPath, $user, $learningPathTreeNode);

        if (count($learningPathAttempts) == 0)
        {
            return 0;
        }

        foreach ($learningPathAttempts as $learningPathAttempt)
        {
            $totalScore += (int) $learningPathAttempt->get_score();
        }

        return round($totalScore / count($learningPathAttempts), 2);
    }

    /**
     * Returns the maximum score of the given user in the given LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return int
     */
    public function getMaximumScoreInLearningPathTreeNode(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);

        $maximumScore = 0;

        $learningPathAttempts = $this->getLearningPathTreeNodeAttempts($learningPath, $user, $learningPathTreeNode);

        foreach ($learningPathAttempts as $learningPathAttempt)
        {
            $maximumScore = $maximumScore < $learningPathAttempt->get_score() ?
                (int) $learningPathAttempt->get_score() : $maximumScore;
        }

        return $maximumScore;
    }

    /**
     * Returns the minimum score of the given user in the given LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return int
     */
    public function getMinimumScoreInLearningPathTreeNode(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);

        $minimumScore = null;

        $learningPathAttempts = $this->getLearningPathTreeNodeAttempts($learningPath, $user, $learningPathTreeNode);

        foreach ($learningPathAttempts as $learningPathAttempt)
        {
            $minimumScore = is_null($minimumScore) || $minimumScore > $learningPathAttempt->get_score() ?
                (int) $learningPathAttempt->get_score() : $minimumScore;
        }

        return $minimumScore;
    }

    /**
     * Returns the score for the last attempt of the given user in the given LearningPathTreeNode
     *
     * @param LearningPath $learningPath
     * @param User $user
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return string
     */
    public function getLastAttemptScoreForLearningPathTreeNode(
        LearningPath $learningPath, User $user, LearningPathTreeNode $learningPathTreeNode
    )
    {
        $this->validateLearningPathTreeNodeIsAssessment($learningPathTreeNode);
        $learningPathAttempts = $this->getLearningPathTreeNodeAttempts($learningPath, $user, $learningPathTreeNode);

        $learningPathAttempt = array_pop($learningPathAttempts);

        return (int) $learningPathAttempt->get_score();
    }
}