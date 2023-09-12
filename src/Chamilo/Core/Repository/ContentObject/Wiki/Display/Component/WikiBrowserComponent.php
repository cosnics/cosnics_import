<?php
namespace Chamilo\Core\Repository\ContentObject\Wiki\Display\Component;

use Chamilo\Core\Repository\ContentObject\Wiki\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Wiki\Display\Table\WikiPageTableRenderer;
use Chamilo\Core\Repository\ContentObject\Wiki\Storage\DataManager;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Table\RequestTableParameterValuesCompiler;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\ContainsCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * @package Chamilo\Core\Repository\ContentObject\Wiki\Display\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class WikiBrowserComponent extends Manager implements DelegateComponent
{

    private $owner;

    public function run()
    {
        $this->owner = $this->get_root_content_object()->get_id();

        if ($this->get_root_content_object() != null)
        {
            $html = [];

            $html[] = $this->render_header();
            $html[] = $this->renderTable();
            $html[] = $this->render_footer();

            return implode(PHP_EOL, $html);
        }
    }

    public function addAdditionalBreadcrumbs(BreadcrumbTrail $breadcrumbtrail): void
    {
    }

    public function getRequestTableParameterValuesCompiler(): RequestTableParameterValuesCompiler
    {
        return $this->getService(RequestTableParameterValuesCompiler::class);
    }

    public function getWikiPageTableRenderer(): WikiPageTableRenderer
    {
        return $this->getService(WikiPageTableRenderer::class);
    }

    public function get_condition()
    {
        // search condition
        $condition = $this->get_search_condition();

        // append with extra conditions
        $owner_condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
            ), new StaticConditionVariable($this->owner)
        );
        if ($condition)
        {
            $conditions = [];
            $conditions[] = $condition;
            $conditions[] = $owner_condition;
            $condition = new AndCondition($conditions);
        }
        else
        {
            $condition = $owner_condition;
        }

        return $condition;
    }

    public function get_search_condition()
    {
        $query = $this->getButtonToolBarRenderer()->getSearchForm()->getQuery();
        if (isset($query) && $query != '')
        {
            $conditions[] = new ContainsCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_TITLE), $query
            );
            $conditions[] = new ContainsCondition(
                new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_DESCRIPTION), $query
            );

            return new OrCondition($conditions);
        }

        return null;
    }

    protected function renderTable(): string
    {
        $totalNumberOfItems = DataManager::count_complex_wiki_pages(
            ComplexContentObjectItem::class, new DataClassCountParameters($this->get_condition())
        );
        $wikiPageTableRenderer = $this->getWikiPageTableRenderer();

        $tableParameterValues = $this->getRequestTableParameterValuesCompiler()->determineParameterValues(
            $wikiPageTableRenderer->getParameterNames(), $wikiPageTableRenderer->getDefaultParameterValues(),
            $totalNumberOfItems
        );

        $wikiPages = DataManager::retrieve_complex_wiki_pages(
            ComplexContentObjectItem::class, new DataClassRetrievesParameters(
                $this->get_condition(), $tableParameterValues->getNumberOfItemsPerPage(),
                $tableParameterValues->getOffset(), $wikiPageTableRenderer->determineOrderBy($tableParameterValues)
            )
        );

        return $wikiPageTableRenderer->legacyRender($this, $tableParameterValues, $wikiPages);
    }
}
