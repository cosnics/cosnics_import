<?php
namespace Chamilo\Core\Repository\Workspace\Repository;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\WorkspaceContentObjectRelation;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;

/**
 *
 * @package Chamilo\Core\Repository\Workspace\Repository
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class ContentObjectRelationRepository
{

    /**
     *
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param \Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface $workspaceImplementation
     *
     * @return boolean
     */
    public function findContentObjectInWorkspace(ContentObject $contentObject,
        WorkspaceInterface $workspaceImplementation)
    {
        $relationConditions = array();

        $relationConditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                WorkspaceContentObjectRelation :: class_name(),
                WorkspaceContentObjectRelation :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($contentObject->getId()));
        $relationConditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                WorkspaceContentObjectRelation :: class_name(),
                WorkspaceContentObjectRelation :: PROPERTY_WORKSPACE_ID),
            new StaticConditionVariable($workspaceImplementation->getId()));

        $relationCondition = new AndCondition($relationConditions);

        return DataManager :: count(
            WorkspaceContentObjectRelation :: class_name(),
            new DataClassCountParameters($relationCondition)) > 0;
    }

    /**
     *
     * @param \Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace $workspace
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @return \Chamilo\Libraries\Storage\DataClass\DataClass
     */
    public function findContentObjectRelationForWorkspaceAndContentObject(Workspace $workspace,
        ContentObject $contentObject)
    {
        $relationConditions = array();

        $relationConditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                WorkspaceContentObjectRelation :: class_name(),
                WorkspaceContentObjectRelation :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($contentObject->getId()));

        $relationConditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                WorkspaceContentObjectRelation :: class_name(),
                WorkspaceContentObjectRelation :: PROPERTY_WORKSPACE_ID),
            new StaticConditionVariable($workspace->getId()));

        $relationCondition = new AndCondition($relationConditions);

        return DataManager :: retrieve(
            WorkspaceContentObjectRelation :: class_name(),
            new DataClassRetrieveParameters($relationCondition));
    }

    /**
     *
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @return integer[]
     */
    public function findWorkspaceIdentifiersForContentObject(ContentObject $contentObject)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                WorkspaceContentObjectRelation :: class_name(),
                WorkspaceContentObjectRelation :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($contentObject->getId()));

        return DataManager :: distinct(
            WorkspaceContentObjectRelation :: class_name(),
            new DataClassDistinctParameters($condition, WorkspaceContentObjectRelation :: PROPERTY_WORKSPACE_ID));
    }
}