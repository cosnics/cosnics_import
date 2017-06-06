<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Integration\Chamilo\Core\Repository\Publication\Service;

use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\Tree;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\TreeNode;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\TreeNodeDataService;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\TreeBuilder;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\LearningPath;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\TreeNodeData;
use Chamilo\Core\Repository\Publication\Storage\DataClass\Attributes;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Workspace\Repository\ContentObjectRepository;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\OrderBy;

/**
 * Service to manage the repository publication functionality to check and provide publication information about
 * one or multiple given content objects in the learning paths
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class LearningPathPublicationService
{
    /**
     * @var TreeNodeDataService
     */
    protected $treeNodeDataService;

    /**
     * @var TreeBuilder
     */
    protected $treeBuilder;

    /**
     * @var ContentObjectRepository
     */
    protected $contentObjectRepository;

    /**
     * @var Tree[]
     */
    protected $treeCache;

    /**
     * LearningPathPublicationService constructor.
     *
     * @param TreeNodeDataService $treeNodeDataService
     * @param TreeBuilder $treeBuilder
     * @param ContentObjectRepository $contentObjectRepository
     */
    public function __construct(
        TreeNodeDataService $treeNodeDataService, TreeBuilder $treeBuilder,
        ContentObjectRepository $contentObjectRepository
    )
    {
        $this->treeNodeDataService = $treeNodeDataService;
        $this->treeBuilder = $treeBuilder;
        $this->contentObjectRepository = $contentObjectRepository;
    }

    /**
     * Checks whether or not one of the given content objects (identified by their id) is published in at
     * least one learning path
     *
     * @param array $contentObjectIds
     *
     * @return bool
     */
    public function areContentObjectsPublished($contentObjectIds = array())
    {
        return count($this->treeNodeDataService->getTreeNodesDataByContentObjects($contentObjectIds)) > 0;
    }

    /**
     * Deletes the learning path children (and their child nodes) by a given content object id in each learning path
     *
     * @param int $contentObjectId
     */
    public function deleteContentObjectPublicationsByObjectId($contentObjectId)
    {
        $treeNodesData =
            $this->treeNodeDataService->getTreeNodesDataByContentObjects(array($contentObjectId));

        foreach ($treeNodesData as $treeNodeData)
        {
            $tree = $this->getTreeForTreeNodeData($treeNodeData);
            foreach ($tree->getTreeNodes() as $treeNode)
            {
                if ($treeNode->getContentObject()->getId() != $contentObjectId)
                {
                    continue;
                }

                try
                {
                    $this->treeNodeDataService->deleteContentObjectFromLearningPath($treeNode);
                }
                catch (\Exception $ex)
                {
                }
            }
        }
    }

    /**
     * Deletes the learning path child (and their child nodes) by a given learning path child id
     *
     * @param int $treeNodeDataId
     */
    public function deleteContentObjectPublicationsByTreeNodeDataId($treeNodeDataId)
    {
        $treeNode = $this->getTreeNodeByTreeNodeDataId($treeNodeDataId);
        $this->treeNodeDataService->deleteContentObjectFromLearningPath($treeNode);
    }

    /**
     * Updates the content object id in the given learning path child (identified by id)
     *
     * @param int $treeNodeDataId
     * @param int $newContentObjectId
     */
    public function updateContentObjectIdInTreeNodeData($treeNodeDataId, $newContentObjectId)
    {
        $treeNode = $this->getTreeNodeByTreeNodeDataId($treeNodeDataId);

        $newContentObject = new ContentObject();
        $newContentObject->setId($newContentObjectId);

        $this->treeNodeDataService->updateContentObjectInTreeNodeData(
            $treeNode, $newContentObject
        );
    }

    /**
     * Returns the ContentObject publication attributes for a given learning path child (identified by id)
     *
     * @param int $treeNodeDataId
     *
     * @return Attributes
     */
    public function getContentObjectPublicationAttributesForTreeNodeData($treeNodeDataId)
    {
        $treeNodeData = $this->treeNodeDataService->getTreeNodeDataById($treeNodeDataId);

        return $this->getAttributesForTreeNodeData($treeNodeData);
    }

    /**
     * Returns the ContentObject publication attributes for a given content object (identified by id)
     *
     * @param int $contentObjectId
     *
     * @return Attributes[]
     */
    public function getContentObjectPublicationAttributesForContentObject($contentObjectId)
    {
        $treeNodesData =
            $this->treeNodeDataService->getTreeNodesDataByContentObjects(array($contentObjectId));

        $attributes = array();

        foreach ($treeNodesData as $treeNodeData)
        {
            $attributes[] = $this->getAttributesForTreeNodeData($treeNodeData);
        }

        return $attributes;
    }

    /**
     * Returns the ContentObject publication attributes for a given user (identified by id)
     *
     * @param int $userId
     *
     * @return Attributes[]
     */
    public function getContentObjectPublicationAttributesForUser($userId)
    {
        $treeNodesData = $this->treeNodeDataService->getTreeNodesDataByUserId((int) $userId);

        $attributes = array();

        foreach ($treeNodesData as $treeNodeData)
        {
            $attributes[] = $this->getAttributesForTreeNodeData($treeNodeData);
        }

        return $attributes;
    }

    /**
     * Counts the ContentObject publication attributes for a given content object (identified by id)
     *
     * @param int $contentObjectId
     *
     * @return int
     */
    public function countContentObjectPublicationAttributesForContentObject($contentObjectId)
    {
        return count($this->treeNodeDataService->getTreeNodesDataByContentObjects(array($contentObjectId)));
    }

    /**
     * Counts the ContentObject publication attributes for a given user (identified by id)
     *
     * @param int $userId
     *
     * @return int
     */
    public function countContentObjectPublicationAttributesForUser($userId)
    {
        return count($this->treeNodeDataService->getTreeNodesDataByUserId((int) $userId));
    }

    /**
     * Returns a learning path tree node by a given learning path child identifier
     *
     * @param int $treeNodeDataId
     *
     * @return TreeNode
     */
    protected function getTreeNodeByTreeNodeDataId($treeNodeDataId)
    {
        $treeNodeData = $this->treeNodeDataService->getTreeNodeDataById($treeNodeDataId);

        $tree = $this->getTreeForTreeNodeData($treeNodeData);
        $treeNode = $tree->getTreeNodeById((int) $treeNodeDataId);

        return $treeNode;
    }

    /**
     * Builds the learning path tree that belongs to a given learning path child
     *
     * @param TreeNodeData $treeNodeData
     *
     * @return Tree
     */
    protected function getTreeForTreeNodeData(TreeNodeData $treeNodeData)
    {
        if (!array_key_exists($treeNodeData->getLearningPathId(), $this->treeCache))
        {
            $learningPath = $this->getLearningPathByTreeNodeData($treeNodeData);

            $this->treeCache[$treeNodeData->getLearningPathId()] =
                $this->treeBuilder->buildTree($learningPath);
        }

        return $this->treeCache[$treeNodeData->getLearningPathId()];
    }

    /**
     * Returns the learning path for the given learning path child
     *
     * @param TreeNodeData $treeNodeData
     *
     * @return LearningPath
     */
    protected function getLearningPathByTreeNodeData(TreeNodeData $treeNodeData)
    {
        $learningPath = $this->contentObjectRepository->findById($treeNodeData->getLearningPathId());

        if (!$learningPath instanceof LearningPath)
        {
            throw new \RuntimeException(
                sprintf(
                    'The given learning path child with id %s is found in a learning path that doesn\'t exist',
                    $treeNodeData->getId()
                )
            );
        }

        return $learningPath;
    }

    /**
     * Builds the publication attributes for the given learning path child
     *
     * @param TreeNodeData $treeNodeData
     *
     * @return Attributes
     */
    protected function getAttributesForTreeNodeData(TreeNodeData $treeNodeData)
    {
        $learningPath = $this->getLearningPathByTreeNodeData($treeNodeData);
        $contentObject = $this->contentObjectRepository->findById($treeNodeData->getContentObjectId());

        $attributes = new Attributes();
        $attributes->setId($treeNodeData->getId());
        $attributes->set_application('Chamilo\Core\Repository\ContentObject\LearningPath');
        $attributes->set_publisher_id($learningPath->get_owner_id());
        $attributes->set_date($contentObject->get_creation_date());
        $attributes->set_location($learningPath->get_title());
        $attributes->set_url(null);
        $attributes->set_title($contentObject->get_title());
        $attributes->set_content_object_id($treeNodeData->getContentObjectId());

        return $attributes;
    }

}