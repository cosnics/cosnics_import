<?php
namespace Chamilo\Core\Repository\Table\ContentObject\Table;

// use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTableDataProvider;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Core\Repository\Workspace\Repository\ContentObjectRepository;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectService;
use Chamilo\Core\Repository\Filter\Renderer\ConditionFilterRenderer;
use Chamilo\Core\Repository\Filter\FilterData;

class RepositoryTableDataProvider extends DataClassTableDataProvider
{

    public function retrieve_data($condition, $offset, $count, $orderProperty = null)
    {
        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $orderProperty);
        
        $contentObjectService = new ContentObjectService(new ContentObjectRepository());
        return $contentObjectService->getContentObjectsForWorkspace(
            $this->get_component()->get_repository_browser()->getWorkspace(), 
            ConditionFilterRenderer :: factory(FilterData :: get_instance()), 
            $count, 
            $offset, 
            $orderProperty);
        
        // return DataManager :: retrieve_active_content_objects($this->get_table()->get_type(), $parameters);
    }

    public function count_data($condition)
    {
        $parameters = new DataClassCountParameters($condition);
        
        $contentObjectService = new ContentObjectService(new ContentObjectRepository());
        return $contentObjectService->countContentObjectsForWorkspace(
            $this->get_component()->get_repository_browser()->getWorkspace(), 
            ConditionFilterRenderer :: factory(FilterData :: get_instance()));
        
        // return DataManager :: count_active_content_objects($this->get_table()->get_type(), $parameters);
    }
}