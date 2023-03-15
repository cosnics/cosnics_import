<?php
namespace Chamilo\Core\Repository\Common\Renderer\Type;

use Chamilo\Core\Repository\Common\Renderer\ContentObjectRenderer;
use Chamilo\Core\Repository\Filter\FilterData;
use Chamilo\Core\Repository\Filter\Renderer\ConditionFilterRenderer;
use Chamilo\Core\Repository\Selector\TypeSelector;
use Chamilo\Core\Repository\Service\ContentObjectActionRenderer;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectService;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Format\Slideshow\SlideshowRenderer;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonSearchForm;
use Chamilo\Libraries\Platform\ChamiloRequest;

/**
 * @package Chamilo\Core\Repository\Common\Renderer\Type
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 */
class SlideshowContentObjectRenderer extends ContentObjectRenderer
{
    protected ContentObjectActionRenderer $contentObjectActionRenderer;

    protected ContentObjectService $contentObjectService;

    protected ChamiloRequest $request;

    protected SlideshowRenderer $slideshowRenderer;

    protected Workspace $workspace;

    public function __construct(
        ContentObjectActionRenderer $contentObjectActionRenderer, ContentObjectService $contentObjectService,
        ChamiloRequest $request, SlideshowRenderer $slideshowRenderer, Workspace $workspace
    )
    {
        $this->contentObjectActionRenderer = $contentObjectActionRenderer;
        $this->contentObjectService = $contentObjectService;
        $this->request = $request;
        $this->slideshowRenderer = $slideshowRenderer;
        $this->workspace = $workspace;
    }

    public function render(Application $application): string
    {
        $workspace = $this->getWorkspace();
        $contentObjectService = $this->getContentObjectService();

        $slideshowIndex = $this->getRequest()->query->get(SlideshowRenderer::PARAM_INDEX, 0);

        $filterData = FilterData::getInstance($workspace);

        $contentObjectCount = $contentObjectService->countContentObjectsByTypeForWorkspace(
            $filterData->getTypeDataClass(), $workspace, ConditionFilterRenderer::factory($filterData, $workspace)
        );

        if ($contentObjectCount)
        {
            $contentObject = $contentObjectService->getContentObjectsByTypeForWorkspace(
                $filterData->getTypeDataClass(), $workspace, ConditionFilterRenderer::factory($filterData, $workspace),
                1, $slideshowIndex
            )->current();

            $contentObjectActions = $this->getContentObjectActionRenderer()->getActions($contentObject);

            return $this->getSlideshowRenderer()->render(
                $application, $contentObject, $contentObjectCount, $contentObjectActions,
                $this->get_parameters($application)
            );
        }

        return '';
    }

    public function getContentObjectActionRenderer(): ContentObjectActionRenderer
    {
        return $this->contentObjectActionRenderer;
    }

    public function getContentObjectService(): ContentObjectService
    {
        return $this->contentObjectService;
    }

    public function getRequest(): ChamiloRequest
    {
        return $this->request;
    }

    protected function getSlideshowRenderer(): SlideshowRenderer
    {
        return $this->slideshowRenderer;
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function get_parameters(Application $application, $include_search = false): array
    {
        $parameters = $application->get_parameters();

        $selected_types = TypeSelector::get_selection();

        if (is_array($selected_types) && count($selected_types))
        {
            $parameters[TypeSelector::PARAM_SELECTION] = $selected_types;
        }

        $parameters[ButtonSearchForm::PARAM_SIMPLE_SEARCH_QUERY] =
            $application->getButtonToolbarRenderer()->getSearchForm()->getQuery();

        return $parameters;
    }
}
