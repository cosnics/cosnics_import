<?php
namespace Chamilo\Application\Weblcms\Bridge\LearningPath\Assignment\Storage\Repository;

use Chamilo\Application\Weblcms\Bridge\LearningPath\Assignment\Storage\DataClass\Entry;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Tool\Implementation\Ephorus\Storage\DataClass\Request;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\TreeNodeData;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\DataClassParameters;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * @package Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\Repository
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class EphorusRepository extends
    \Chamilo\Core\Repository\ContentObject\Assignment\Integration\Chamilo\Core\Repository\ContentObject\LearningPath\Bridge\Storage\Repository\EphorusRepository
{
    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $contentObjectPublication
     * @param \Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\TreeNodeData $treeNodeData
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return int
     */
    public function countAssignmentEntriesWithRequestsByTreeNodeData(
        ContentObjectPublication $contentObjectPublication, TreeNodeData $treeNodeData, Condition $condition = null
    )
    {
        return $this->countAssignmentEntriesWithRequests(
            $this->getConditionForTreeNodeDataAndPublication($contentObjectPublication, $treeNodeData, $condition)
        );
    }

    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $contentObjectPublication
     * @param \Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\TreeNodeData $treeNodeData
     * @param \Chamilo\Libraries\Storage\Query\DataClassParameters $dataClassParameters
     *
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObject[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function findAssignmentEntriesWithRequestsByTreeNodeData(
        ContentObjectPublication $contentObjectPublication, TreeNodeData $treeNodeData,
        DataClassParameters $dataClassParameters = new DataClassParameters()
    )
    {
        $entryConditions = $this->getConditionForTreeNodeDataAndPublication(
            $contentObjectPublication, $treeNodeData, $dataClassParameters->getCondition()
        );
        $dataClassParameters->setCondition($entryConditions);

        return $this->findAssignmentEntriesWithRequests($dataClassParameters);
    }

    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $contentObjectPublication
     * @param \Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\TreeNodeData $treeNodeData
     * @param int[] $entryIds
     *
     * @return Request[]
     */
    public function findEphorusRequestsForAssignmentEntriesByTreeNodeData(
        ContentObjectPublication $contentObjectPublication, TreeNodeData $treeNodeData, array $entryIds = []
    )
    {
        return $this->findEphorusRequestsForAssignmentEntries(
            $entryIds, $this->getConditionForTreeNodeDataAndPublication($contentObjectPublication, $treeNodeData)
        );
    }

    /**
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $contentObjectPublication
     * @param \Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\TreeNodeData $treeNodeData
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return Condition
     */
    protected function getConditionForTreeNodeDataAndPublication(
        ContentObjectPublication $contentObjectPublication, TreeNodeData $treeNodeData, Condition $condition = null
    )
    {
        $conditions = [];

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Entry::class, Entry::PROPERTY_CONTENT_OBJECT_PUBLICATION_ID),
            new StaticConditionVariable($contentObjectPublication->getId())
        );

        if ($condition instanceof Condition)
        {
            $conditions[] = $condition;
        }

        $condition = new AndCondition($conditions);

        return $this->getConditionForTreeNodeData($treeNodeData, $condition);
    }

    /**
     * @return string
     */
    protected function getEntryClassName()
    {
        return Entry::class;
    }

}