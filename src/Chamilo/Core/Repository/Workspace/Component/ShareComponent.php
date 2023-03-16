<?php
namespace Chamilo\Core\Repository\Workspace\Component;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Workspace\Manager;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectRelationService;
use Chamilo\Core\Repository\Workspace\Table\ShareTableRenderer;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\Glyph\IdentGlyph;
use Chamilo\Libraries\Format\Structure\Toolbar;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\RequestTableParameterValuesCompiler;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\RetrieveProperties;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Translation\Translation;

/**
 * @package Chamilo\Core\Repository\Workspace\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class ShareComponent extends Manager
{

    /**
     * @var int
     */
    private $selectedContentObjectIdentifiers;

    /**
     * @var int
     */
    private $selectedWorkspaceIdentifiers;

    public function run()
    {
        $selectedContentObjectIdentifiers = $this->getSelectedContentObjectIdentifiers();
        $selectedWorkspaceIdentifiers = $this->getSelectedWorkspaceIdentifiers();

        if (empty($selectedContentObjectIdentifiers))
        {
            throw new NoObjectSelectedException(Translation::get('ContentObject'));
        }

        if (!empty($selectedWorkspaceIdentifiers))
        {
            $selectedContentObjectIdentifiers = (array) $this->getRequest()->get(
                \Chamilo\Core\Repository\Manager::PARAM_CONTENT_OBJECT_ID, []
            );

            $selectedContentObjectNumbers = DataManager::distinct(
                ContentObject::class, new DataClassDistinctParameters(
                    new InCondition(
                        new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
                        $selectedContentObjectIdentifiers
                    ), new RetrieveProperties(
                        [
                            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_OBJECT_NUMBER)
                        ]
                    )
                )
            );

            foreach ($selectedWorkspaceIdentifiers as $selectedWorkspaceIdentifier)
            {
                foreach ($selectedContentObjectNumbers as $selectedContentObjectNumber)
                {
                    $this->getContentObjectRelationService()->createContentObjectRelationFromParameters(
                        $selectedWorkspaceIdentifier, $selectedContentObjectNumber, 0
                    );
                }
            }

            $this->redirectWithMessage(
                Translation::get('ContentObjectsShared'), false, [
                    self::PARAM_ACTION => null,
                    \Chamilo\Core\Repository\Manager::PARAM_ACTION => \Chamilo\Core\Repository\Manager::ACTION_BROWSE_CONTENT_OBJECTS
                ]
            );
        }
        else
        {
            $contentObjectIdentifiers = $this->getSelectedContentObjectIdentifiers();

            if (count($contentObjectIdentifiers) >= 1)
            {
                $contentObjects = DataManager::retrieves(
                    ContentObject::class, new DataClassRetrievesParameters(
                        new InCondition(
                            new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
                            $contentObjectIdentifiers
                        )
                    )
                );

                $toolbar = new Toolbar(Toolbar::TYPE_VERTICAL);

                foreach ($contentObjects as $contentObject)
                {
                    $viewUrl = new Redirect(
                        [
                            Application::PARAM_CONTEXT => \Chamilo\Core\Repository\Manager::context(),
                            \Chamilo\Core\Repository\Manager::PARAM_ACTION => \Chamilo\Core\Repository\Manager::ACTION_VIEW_CONTENT_OBJECTS,
                            \Chamilo\Core\Repository\Manager::PARAM_CONTENT_OBJECT_ID => $contentObject->getId()
                        ]
                    );

                    $toolbar->add_item(
                        new ToolbarItem(
                            $contentObject->get_title(), $contentObject->getGlyph(
                            IdentGlyph::SIZE_MINI, true, ['fa-fw']
                        ), $viewUrl->getUrl(), ToolbarItem::DISPLAY_ICON_AND_LABEL, false, null, '_blank'
                        )
                    );
                }

                $selectedObjectsPreviews = [];

                $selectedObjectsPreviews[] = '<div class="panel panel-default">';
                $selectedObjectsPreviews[] = '<div class="panel-heading">';
                $selectedObjectsPreviews[] = '<h3 class="panel-title">';
                $selectedObjectsPreviews[] = Translation::get('SelectedContentObjects');
                $selectedObjectsPreviews[] = '</h3>';
                $selectedObjectsPreviews[] = '</div>';
                $selectedObjectsPreviews[] = '<div class="panel-body">';
                $selectedObjectsPreviews[] = $toolbar->as_html();
                $selectedObjectsPreviews[] = '</div>';
                $selectedObjectsPreviews[] = '</div>';

                $selectedObjectsPreview = implode(PHP_EOL, $selectedObjectsPreviews);
            }

            $html = [];

            $html[] = $this->render_header();

            $parameters = [];
            $parameters[self::PARAM_CONTEXT] = Manager::context();
            $parameters[self::PARAM_ACTION] = self::ACTION_CREATE;

            $redirect = new Redirect($parameters);
            $url = $redirect->getUrl();

            $html[] = '<div class="alert alert-info" role="alert">' .
                $this->getTranslation('ShareInformation', ['WORKSPACE_URL' => $url]) . '</div>';

            $html[] = $selectedObjectsPreview;
            $html[] = '<h3 style="margin-bottom: 30px;">' . $this->getTranslation('ShareInWorkspaces') . '</h3>';
            $html[] = $this->renderTable();
            $html[] = $this->render_footer();

            return implode(PHP_EOL, $html);
        }
    }

    /**
     * @see \Chamilo\Core\Repository\Manager::getAdditionalParameters()
     */
    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = \Chamilo\Core\Repository\Manager::PARAM_CONTENT_OBJECT_ID;

        return parent::getAdditionalParameters($additionalParameters);
    }

    protected function getContentObjectRelationService(): ContentObjectRelationService
    {
        return $this->getService(ContentObjectRelationService::class);
    }

    public function getRequestTableParameterValuesCompiler(): RequestTableParameterValuesCompiler
    {
        return $this->getService(RequestTableParameterValuesCompiler::class);
    }

    /**
     * @return int
     */
    public function getSelectedContentObjectIdentifiers()
    {
        if (!isset($this->selectedContentObjectIdentifiers))
        {
            $this->selectedContentObjectIdentifiers = (array) $this->getRequest()->get(
                \Chamilo\Core\Repository\Manager::PARAM_CONTENT_OBJECT_ID, []
            );
        }

        return $this->selectedContentObjectIdentifiers;
    }

    /**
     * @return int
     */
    public function getSelectedWorkspaceIdentifiers()
    {
        if (!isset($this->selectedWorkspaceIdentifiers))
        {
            $this->selectedWorkspaceIdentifiers = (array) $this->getRequest()->get(
                Manager::PARAM_SELECTED_WORKSPACE_ID, []
            );
        }

        return $this->selectedWorkspaceIdentifiers;
    }

    protected function getShareTableRenderer(): ShareTableRenderer
    {
        return $this->getService(ShareTableRenderer::class);
    }

    /**
     * Translation method helper
     *
     * @param string $variable
     * @param array $parameters
     *
     * @return string
     */
    protected function getTranslation($variable, $parameters = [])
    {
        return Translation::getInstance()->getTranslation($variable, $parameters, Manager::context());
    }

    protected function renderTable(): string
    {
        $totalNumberOfItems =
            $this->getContentObjectRelationService()->countAvailableWorkspacesForContentObjectIdentifiersAndUser(
                $this->getSelectedContentObjectIdentifiers(), $this->getUser()
            );
        $shareTableRenderer = $this->getShareTableRenderer();

        $tableParameterValues = $this->getRequestTableParameterValuesCompiler()->determineParameterValues(
            $shareTableRenderer->getParameterNames(), $shareTableRenderer->getDefaultParameterValues(),
            $totalNumberOfItems
        );

        $workspaces =
            $this->getContentObjectRelationService()->getAvailableWorkspacesForContentObjectIdentifiersAndUser(
                $this->getSelectedContentObjectIdentifiers(), $this->getUser(),
                $tableParameterValues->getNumberOfItemsPerPage(), $tableParameterValues->getOffset(),
                $shareTableRenderer->determineOrderBy($tableParameterValues)
            );

        return $shareTableRenderer->render($tableParameterValues, $workspaces);
    }
}
