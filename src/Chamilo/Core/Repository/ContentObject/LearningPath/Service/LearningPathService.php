<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Service;

use Chamilo\Core\Repository\Common\Action\ContentObjectCopier;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\TreeNode;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\LearningPath;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\TreeNodeData;
use Chamilo\Core\Repository\ContentObject\Section\Storage\DataClass\Section;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Workspace\PersonalWorkspace;
use Chamilo\Core\Repository\Workspace\Repository\ContentObjectRepository;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;

/**
 * Service to manage learning paths
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class LearningPathService
{
    /**
     * @var ContentObjectRepository
     */
    protected $contentObjectRepository;

    /**
     * @var TreeBuilder
     */
    protected $treeBuilder;

    /**
     * @var TreeNodeDataService
     */
    protected $treeNodeDataService;

    /**
     * LearningPathService constructor.
     *
     * @param ContentObjectRepository $contentObjectRepository
     * @param TreeBuilder $treeBuilder
     * @param TreeNodeDataService $treeNodeDataService
     */
    public function __construct(
        ContentObjectRepository $contentObjectRepository, TreeBuilder $treeBuilder,
        TreeNodeDataService $treeNodeDataService
    )
    {
        $this->contentObjectRepository = $contentObjectRepository;
        $this->treeBuilder = $treeBuilder;
        $this->treeNodeDataService = $treeNodeDataService;
    }

    /**
     * Returns a list of learning paths
     *
     * @return LearningPath[]
     */
    public function getLearningPaths()
    {
        /** @var LearningPath[] $learningPaths */
        $learningPaths =
            $this->contentObjectRepository->findAll(LearningPath::class_name(), new DataClassRetrievesParameters())
                ->as_array();

        return $learningPaths;
    }

    /**
     * Copies one or multiple nodes from a given LearningPath to a given TreeNode
     *
     * @param TreeNode $toNode
     * @param LearningPath $fromLearningPath
     * @param User $user
     * @param array $selectedNodeIds
     * @param bool $copyInsteadOfReuse
     */
    public function copyNodesFromLearningPath(
        TreeNode $toNode, LearningPath $fromLearningPath, User $user, $selectedNodeIds = array(),
        $copyInsteadOfReuse = false
    )
    {
        /** @var LearningPath $rootLearningPath */
        $rootLearningPath = $toNode->getTree()->getRoot()->getContentObject();

        $fromTree = $this->treeBuilder->buildTree($fromLearningPath);
        foreach ($selectedNodeIds as $selectedNodeId)
        {
            $selectedNode = $fromTree->getTreeNodeById((int) $selectedNodeId);
            $this->copyNodeAndChildren($rootLearningPath, $toNode, $selectedNode, $user, $copyInsteadOfReuse);
        }
    }

    /**
     * Copies a given node and his children to the given learning path and tree node
     *
     * @param LearningPath $rootLearningPath
     * @param TreeNode $toNode
     * @param TreeNode $fromNode
     * @param User $user
     * @param bool $copyInsteadOfReuse
     */
    protected function copyNodeAndChildren(
        LearningPath $rootLearningPath, TreeNode $toNode, TreeNode $fromNode, User $user,
        $copyInsteadOfReuse = false
    )
    {
        $contentObject = $this->prepareContentObjectForCopy(
            $fromNode, $user, $toNode->getContentObject()->get_parent_id(), $copyInsteadOfReuse
        );

        $treeNodeData = $this->copyTreeNodeData($rootLearningPath, $toNode, $fromNode, $user, $contentObject);

        $newNode = new TreeNode($toNode->getTree(), $contentObject, $treeNodeData);
        $toNode->addChildNode($newNode);

        foreach ($fromNode->getChildNodes() as $childNode)
        {
            $this->copyNodeAndChildren($rootLearningPath, $newNode, $childNode, $user, $copyInsteadOfReuse);
        }
    }

    /**
     * Prepares the content object for the copy action.
     *
     * If the content object is a root node (e.g. a Learning Path) the
     * content object is always converted to a new Section.
     *
     * If the copy flag is set, the content object will be physically copied
     *
     * @param TreeNode $fromNode
     * @param User $user
     * @param int $categoryId
     * @param bool $copyInsteadOfReuse
     *
     * @return ContentObject
     */
    protected function prepareContentObjectForCopy(
        TreeNode $fromNode, User $user, $categoryId, $copyInsteadOfReuse = false
    )
    {
        if ($fromNode->isRootNode())
        {
            $contentObject = new Section();

            $contentObject->set_owner_id($user->getId());
            $contentObject->set_title($fromNode->getContentObject()->get_title());
            $contentObject->set_description($fromNode->getContentObject()->get_description());

            $contentObject->create();

            return $contentObject;
        }

        if ($copyInsteadOfReuse)
        {
            return $this->copyContentObjectFromNode($fromNode, $user, $categoryId);
        }

        return $fromNode->getContentObject();
    }

    /**
     * Copies a given content object
     *
     * @param TreeNode $node
     * @param User $user
     * @param int $categoryId
     *
     * @return Section|ContentObject
     */
    protected function copyContentObjectFromNode(TreeNode $node, User $user, $categoryId)
    {
        $contentObject = $node->getContentObject();

        $contentObjectCopier = new ContentObjectCopier(
            $user, array($contentObject->getId()), new PersonalWorkspace($contentObject->get_owner()),
            $contentObject->get_owner_id(), new PersonalWorkspace($user), $user->getId(),
            $categoryId
        );

        $newContentObjectIdentifiers = $contentObjectCopier->run();
        return $this->contentObjectRepository->findById(array_pop($newContentObjectIdentifiers));
    }

    /**
     * Copies a learning path child from a given node to a new node
     *
     * @param LearningPath $rootLearningPath
     * @param TreeNode $toNode
     * @param TreeNode $fromNode
     * @param User $user
     * @param ContentObject $contentObject
     *
     * @return TreeNodeData
     */
    protected function copyTreeNodeData(
        LearningPath $rootLearningPath, TreeNode $toNode, TreeNode $fromNode, User $user,
        ContentObject $contentObject
    ): TreeNodeData
    {
        if ($fromNode->isRootNode())
        {
            $treeNodeData = new TreeNodeData();
        }
        else
        {
            $treeNodeData = $fromNode->getTreeNodeData();
        }

        $treeNodeData->setId(null);
        $treeNodeData->setUserId((int) $user->getId());
        $treeNodeData->setLearningPathId((int) $rootLearningPath->getId());
        $treeNodeData->setParentTreeNodeDataId((int) $toNode->getId());
        $treeNodeData->setContentObjectId((int) $contentObject->getId());
        $treeNodeData->setAddedDate(time());

        $this->treeNodeDataService->createTreeNodeData($treeNodeData);

        return $treeNodeData;
    }
}