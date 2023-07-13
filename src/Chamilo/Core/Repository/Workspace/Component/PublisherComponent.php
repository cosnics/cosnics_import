<?php
namespace Chamilo\Core\Repository\Workspace\Component;

use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Core\Repository\Filter\FilterData;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Viewer\Architecture\Traits\ViewerTrait;
use Chamilo\Core\Repository\Workspace\Manager;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\WorkspaceContentObjectRelation;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\RetrieveProperties;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Exception;

/**
 * @package Chamilo\Core\Repository\Workspace\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class PublisherComponent extends Manager
{
    use ViewerTrait;

    public function run()
    {
        if (!$this->getCurrentWorkspace() instanceof Workspace)
        {
            throw new Exception(Translation::get('NoValidWorkspace'));
        }

        if (!$this->getRightsService()->canAddContentObjects($this->get_user(), $this->getCurrentWorkspace()))
        {
            throw new NotAllowedException();
        }

        if (!$this->isAnyObjectSelectedInViewer())
        {
            $this->getRequest()->query->set(
                \Chamilo\Core\Repository\Viewer\Manager::PARAM_ACTION,
                \Chamilo\Core\Repository\Viewer\Manager::ACTION_BROWSER
            );

            $applicationConfiguration = new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this);

            $component = $this->getApplicationFactory()->getApplication(
                \Chamilo\Core\Repository\Viewer\Manager::CONTEXT, $applicationConfiguration
            );
            $component->set_excluded_objects($this->getExcludedObjects());
            $component->set_actions([\Chamilo\Core\Repository\Viewer\Manager::ACTION_BROWSER]);

            return $component->run();
        }
        else
        {
            $selectedContentObjectIdentifiers = (array) $this->getObjectsSelectedInviewer();

            $parentId = $this->getRequest()->getFromRequestOrQuery(FilterData::FILTER_CATEGORY);
            $parentId = $parentId ?: 0;

            foreach ($selectedContentObjectIdentifiers as $selectedContentObjectIdentifier)
            {
                $contentObject = DataManager::retrieve_by_id(
                    ContentObject::class, $selectedContentObjectIdentifier
                );

                $this->getContentObjectRelationService()->createContentObjectRelationFromParameters(
                    $this->getCurrentWorkspace()->getId(), $contentObject->get_object_number(), $parentId
                );
            }

            $this->redirectWithMessage(
                Translation::get('ContentObjectsAddedToWorkspace'), false, [
                    \Chamilo\Core\Repository\Manager::PARAM_ACTION => \Chamilo\Core\Repository\Manager::ACTION_BROWSE_CONTENT_OBJECTS,
                    self::PARAM_ACTION => null
                ]
            );
        }
    }

    protected function getContentObjectRelationService(): ContentObjectRelationService
    {
        return $this->getService(ContentObjectRelationService::class);
    }

    public function getCurrentWorkspace()
    {
        return $this->get_application()->getCurrentWorkspace();
    }

    // TODO: This should return ALL ids of ALL content object ids attached to the object numbers

    public function getExcludedObjects()
    {
        $workspace = $this->get_application()->getCurrentWorkspace();

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                WorkspaceContentObjectRelation::class, WorkspaceContentObjectRelation::PROPERTY_WORKSPACE_ID
            ), new StaticConditionVariable($workspace->getId())
        );

        $contentObjectNumbers = DataManager::distinct(
            WorkspaceContentObjectRelation::class, new DataClassDistinctParameters(
                $condition, new RetrieveProperties(
                    [
                        new PropertyConditionVariable(
                            WorkspaceContentObjectRelation::class,
                            WorkspaceContentObjectRelation::PROPERTY_CONTENT_OBJECT_ID
                        )
                    ]
                )
            )
        );

        return DataManager::distinct(
            ContentObject::class, new DataClassDistinctParameters(
                new InCondition(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OBJECT_NUMBER),
                    $contentObjectNumbers
                ), new RetrieveProperties(
                    [new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID)]
                )
            )
        );
    }

    /**
     * @return string[]
     */
    public function get_allowed_content_object_types()
    {
        $registrations =
            $this->getRegistrationConsulter()->getRegistrationsByType('Chamilo\Core\Repository\ContentObject');

        foreach ($registrations as $registration)
        {
            $namespace = $registration[Registration::PROPERTY_CONTEXT];
            $types[] = $namespace . '\Storage\DataClass\\' .
                ClassnameUtilities::getInstance()->getPackageNameFromNamespace($namespace);
        }

        return $types;
    }
}
